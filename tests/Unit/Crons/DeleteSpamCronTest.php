<?php

namespace AntispamBee\Tests\Unit\Crons;

use AntispamBee\Crons\DeleteSpamCron;
use AntispamBee\GeneralOptions\DeleteOldSpam;
use AntispamBee\Helpers\Settings;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Unit tests for {@see DeleteSpamCron}.
 *
 * These cover the option paths the cron reads. The names are built by
 * {@see DeleteOldSpam::get_option_name()}, which is also what the settings page and the v2
 * migration write, so reading them through the same helper keeps both sides in sync.
 */
class DeleteSpamCronTest extends TestCase {

	/**
	 * Stub the plugin options as they are stored in the database.
	 *
	 * @param array<string, mixed> $general Values for the `general` section.
	 *
	 * @return void
	 */
	private function stub_settings( array $general ): void {
		// Reading the settings runs the update check first, so report the database as current to
		// keep the v2 migration out of these tests. `wp_cache_get()` and `wp_cache_set()` come
		// from the shared function stubs and cannot be redefined here.
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.1' ] );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) use ( $general ) {
				if ( 'antispambee_db_version' === $name ) {
					return '3.0.0-beta.1';
				}

				if ( Settings::OPTION_NAME === $name ) {
					return [ 'general' => $general ];
				}

				return $default;
			}
		);
	}

	/**
	 * The option names the settings page and the migration actually write.
	 *
	 * @return array<string, string> The active and days option names.
	 */
	private function option_names(): array {
		return [
			'active' => DeleteOldSpam::get_option_name( 'active' ),
			'days'   => DeleteOldSpam::get_option_name( 'delete_spam_cronjob_days' ),
		];
	}

	/**
	 * The option names must match the documented storage keys, since the settings page, the v2
	 * migration and the cron all have to agree on them.
	 *
	 * @return void
	 */
	public function test_option_names_match_the_stored_keys(): void {
		$names = $this->option_names();

		$this->assertSame( 'general_delete_spam_cronjob_enabled_active', $names['active'] );
		$this->assertSame( 'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days', $names['days'] );
	}

	/**
	 * Enabling the setting schedules the event.
	 *
	 * @return void
	 */
	public function test_enabled_setting_schedules_the_event(): void {
		$names = $this->option_names();
		$this->stub_settings(
			[
				$names['active'] => 'on',
				$names['days']   => 14,
			]
		);

		when( 'wp_next_scheduled' )->justReturn( false );
		expect( 'wp_schedule_event' )->once()->with( \Mockery::type( 'int' ), 'daily', DeleteSpamCron::CRONJOB_NAME );

		DeleteSpamCron::maybe_change_cron_state();
	}

	/**
	 * Disabling the setting unschedules the event.
	 *
	 * @return void
	 */
	public function test_disabled_setting_unschedules_the_event(): void {
		$names = $this->option_names();
		$this->stub_settings( [ $names['active'] => '' ] );

		when( 'wp_next_scheduled' )->justReturn( 1234567890 );
		expect( 'wp_clear_scheduled_hook' )->once()->with( DeleteSpamCron::CRONJOB_NAME );

		DeleteSpamCron::maybe_change_cron_state();
	}

	/**
	 * A configuration written by the v2 migration schedules the event too.
	 *
	 * `Handlers\PluginUpdate` writes the same two keys as the settings page, so a migrated site
	 * must behave identically to one configured through the UI.
	 *
	 * @return void
	 */
	public function test_migrated_v2_configuration_schedules_the_event(): void {
		$this->stub_settings(
			[
				'general_delete_spam_cronjob_enabled_active' => 'on',
				'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days' => 30,
			]
		);

		when( 'wp_next_scheduled' )->justReturn( false );
		expect( 'wp_schedule_event' )->once();

		DeleteSpamCron::maybe_change_cron_state();
	}

	/**
	 * The configured retention period is read from the option the settings page writes.
	 *
	 * @return void
	 */
	public function test_configured_days_are_read_from_the_stored_option(): void {
		$names = $this->option_names();
		$this->stub_settings(
			[
				$names['active'] => 'on',
				$names['days']   => 14,
			]
		);

		$this->assertSame( 14, (int) Settings::get_option( $names['days'] ) );
	}
}
