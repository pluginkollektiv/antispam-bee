<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionProperty;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Unit tests for the v2 to v3 option migration in {@see PluginUpdate}.
 */
class PluginUpdateTest extends TestCase {

	/**
	 * Options captured from `update_option()` calls, keyed by option name.
	 *
	 * @var array<string, mixed>
	 */
	private $written_options = [];

	/**
	 * Options removed via `delete_option()`.
	 *
	 * @var string[]
	 */
	private $deleted_options = [];

	/**
	 * The simulated option store that `get_option()` reads from.
	 *
	 * @var array<string, mixed>
	 */
	private $stored_options = [];

	/**
	 * Reset the memoized migration state and stub the option and plugin-file functions.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_static( 'db_update_triggered', false );
		$this->reset_static( 'db_version_is_current', null );

		FailingPluginUpdate::$calls             = 0;
		FailingPluginUpdate::$state_during_call = null;

		$this->written_options = [];
		$this->deleted_options = [];
		$this->stored_options  = [];

		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.1' ] );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return array_key_exists( $name, $this->stored_options ) ? $this->stored_options[ $name ] : $default;
			}
		);
		when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->written_options[ $name ] = $value;
				$this->stored_options[ $name ]  = $value;

				return true;
			}
		);
		when( 'delete_option' )->alias(
			function ( $name ) {
				$this->deleted_options[] = $name;
				unset( $this->stored_options[ $name ] );

				return true;
			}
		);
	}

	/**
	 * Reset a private static property of the handler between tests.
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Value to reset it to.
	 *
	 * @return void
	 */
	private function reset_static( string $name, $value ): void {
		$property = new ReflectionProperty( PluginUpdate::class, $name );
		$property->setAccessible( true );
		$property->setValue( null, $value );
	}

	/**
	 * Seed the simulated option store.
	 *
	 * @param array<string, mixed> $stored Option values keyed by option name.
	 *
	 * @return void
	 */
	private function stub_options( array $stored ): void {
		$this->stored_options = $stored;
	}

	/**
	 * A legacy option array that predates most v2 options must migrate without warnings.
	 *
	 * PHPUnit is configured with `convertWarningsToExceptions`, so an `Undefined array key`
	 * warning raised by the migration fails this test.
	 *
	 * @return void
	 */
	public function test_partial_legacy_options_migrate_without_warnings(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check' => 1,
					'spam_ip'      => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $this->written_options );
	}

	/**
	 * Legacy options that are present and enabled are migrated as enabled, and keys that are
	 * absent from the legacy array are migrated as disabled rather than raising a warning.
	 *
	 * @return void
	 */
	public function test_missing_legacy_keys_migrate_as_disabled(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check' => 1,
					'spam_ip'      => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( 'on', $comment['rule_asb_regexp_active'], 'A present, enabled legacy option stays enabled.' );
		$this->assertSame( 'on', $comment['rule_asb_db_spam_active'], 'A present, enabled legacy option stays enabled.' );

		$this->assertSame( '', $comment['post_processor_asb_send_email_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_bbcode_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_country_spam_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $comment['rule_asb_lang_spam_active'], 'A missing legacy option migrates as disabled.' );

		$general = $this->written_options[ Settings::OPTION_NAME ]['general'];

		$this->assertSame( '', $general['general_delete_spam_cronjob_enabled_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_statistics_on_dashboard_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_ignore_linkbacks_active'], 'A missing legacy option migrates as disabled.' );
		$this->assertSame( '', $general['general_delete_data_on_uninstall_active'], 'A missing legacy option migrates as disabled.' );
	}

	/**
	 * A legacy option that is explicitly disabled must not be migrated as enabled.
	 *
	 * @return void
	 */
	public function test_disabled_legacy_options_stay_disabled(): void {
		$this->stub_options(
			[
				'antispam_bee'          => [
					'regexp_check'  => 0,
					'bbcode_check'  => '',
					'country_code'  => false,
					'cronjob_enable' => 1,
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( '', $comment['rule_asb_regexp_active'] );
		$this->assertSame( '', $comment['rule_asb_bbcode_active'] );
		$this->assertSame( '', $comment['rule_asb_country_spam_active'] );

		$general = $this->written_options[ Settings::OPTION_NAME ]['general'];

		$this->assertSame( 'on', $general['general_delete_spam_cronjob_enabled_active'] );
	}

	/**
	 * A legacy option value that is not an array at all must not abort the migration.
	 *
	 * @return void
	 */
	public function test_non_array_legacy_options_do_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'          => 'corrupted',
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$comment = $this->written_options[ Settings::OPTION_NAME ]['comment'];

		$this->assertSame( '', $comment['rule_asb_regexp_active'] );
		$this->assertSame( 'on', $comment['rule_asb_honeypot_active'], 'The honeypot rule is always migrated as enabled.' );
	}

	/**
	 * A legacy `translate_lang` that is still a plain string must not abort the migration.
	 *
	 * The option was a single value before the multiselect rework, so a 2.x install that
	 * never re-saved its settings still holds a string here. Passing it into an `array`
	 * parameter raised an uncaught `TypeError`.
	 *
	 * @return void
	 */
	public function test_scalar_translate_lang_does_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'translate_lang' => 'de',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $this->written_options );
		$this->assertSame(
			[ 'de' => 'on' ],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_lang_spam_allowed'],
			'A single legacy language is migrated as one selected value.'
		);
	}

	/**
	 * A legacy `ignore_reasons` that is still a plain string must migrate through the mapping.
	 *
	 * @return void
	 */
	public function test_scalar_ignore_reasons_does_not_abort_the_migration(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'ignore_reasons' => 'css',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			[ 'asb-honeypot' => 'on' ],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['post_processor_asb_delete_for_reasons_reasons'],
			'The legacy reason slug is mapped to its 3.0 equivalent.'
		);
	}

	/**
	 * An empty legacy multiselect value must migrate to an empty selection, not to an empty key.
	 *
	 * @return void
	 */
	public function test_empty_scalar_multiselect_migrates_to_an_empty_selection(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [
					'translate_lang' => '',
				],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			[],
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_lang_spam_allowed']
		);
	}

	/**
	 * A migration that completes clears the failure state and records the new version.
	 *
	 * @return void
	 */
	public function test_a_successful_migration_clears_the_failure_state(): void {
		$this->stub_options(
			[
				'antispam_bee'                   => [ 'regexp_check' => 1 ],
				'antispambee_db_version'         => '1.02',
				'antispambee_db_update_failures' => [
					'version'  => '3.0.0-beta.1',
					'attempts' => 1,
					'message'  => 'Migration exploded',
					'time'     => 1,
				],
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertContains( PluginUpdate::FAILURE_OPTION_NAME, $this->deleted_options );
		$this->assertSame( '3.0.0-beta.1', $this->written_options[ PluginUpdate::DB_VERSION_OPTION_NAME ] );
	}

	/**
	 * A migration that ran records itself, so the next admin page load can report it.
	 *
	 * The request that migrates is usually not one anybody is watching — a WP-CLI update,
	 * a cron run, a front-end hit that read a setting.
	 *
	 * @return void
	 */
	public function test_a_successful_migration_records_the_pending_notice(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [ 'regexp_check' => 1 ],
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame( '1.02', $this->written_options[ PluginUpdate::MIGRATION_NOTICE_OPTION_NAME ] );
	}

	/**
	 * A fresh install has nothing to report: no step ran, so no settings were migrated.
	 *
	 * @return void
	 */
	public function test_a_fresh_install_records_no_pending_notice(): void {
		$this->stub_options( [] );

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayNotHasKey( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME, $this->written_options );
		$this->assertSame( '3.0.0-beta.1', $this->written_options[ PluginUpdate::DB_VERSION_OPTION_NAME ] );
	}

	/**
	 * A failing step records the attempt and leaves the database version untouched.
	 *
	 * The version has to stay stale so the migration is retried, and the failure must not
	 * escape: the request continues on the defaults rather than fataling.
	 *
	 * @return void
	 */
	public function test_a_failing_step_records_an_attempt_and_keeps_the_version_stale(): void {
		$this->stub_options( [ 'antispambee_db_version' => '1.02' ] );

		FailingPluginUpdate::maybe_run_plugin_updated_logic();

		$state = $this->written_options[ PluginUpdate::FAILURE_OPTION_NAME ];

		$this->assertSame( 1, $state['attempts'] );
		$this->assertSame( 'Migration exploded', $state['message'] );
		$this->assertArrayNotHasKey(
			PluginUpdate::DB_VERSION_OPTION_NAME,
			$this->written_options,
			'A failed migration must not mark the database as up-to-date.'
		);
	}

	/**
	 * The attempt is persisted before the step runs, not after it.
	 *
	 * A step killed by a timeout or a fatal never returns, so a counter raised afterwards
	 * would stay at zero and the migration would be retried forever.
	 *
	 * @return void
	 */
	public function test_the_attempt_is_recorded_before_the_step_runs(): void {
		$this->stub_options( [ 'antispambee_db_version' => '1.02' ] );

		FailingPluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertIsArray( FailingPluginUpdate::$state_during_call );
		$this->assertSame( 1, FailingPluginUpdate::$state_during_call['attempts'] );
	}

	/**
	 * Once the cap is reached the migration is not attempted again.
	 *
	 * @return void
	 */
	public function test_the_migration_is_not_attempted_after_the_cap_is_reached(): void {
		$this->stub_options(
			[
				'antispambee_db_version'         => '1.02',
				'antispambee_db_update_failures' => [
					'version'  => '3.0.0-beta.1',
					'attempts' => PluginUpdate::MAX_UPDATE_ATTEMPTS,
					'message'  => 'Migration exploded',
					'time'     => 1,
				],
			]
		);

		FailingPluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame( 0, FailingPluginUpdate::$calls );
		$this->assertSame( [], $this->written_options );
	}

	/**
	 * A failure recorded against another plugin version does not block the migration.
	 *
	 * A release that fixes whatever made the migration fail has to get its own attempts,
	 * so a site that gave up recovers on update instead of needing a manual retry.
	 *
	 * @return void
	 */
	public function test_a_failure_state_from_another_version_is_discarded(): void {
		$this->stub_options(
			[
				'antispam_bee'                   => [ 'regexp_check' => 1 ],
				'antispambee_db_version'         => '1.02',
				'antispambee_db_update_failures' => [
					'version'  => '3.0.0-beta.0',
					'attempts' => PluginUpdate::MAX_UPDATE_ATTEMPTS,
					'message'  => 'Migration exploded',
					'time'     => 1,
				],
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayHasKey( Settings::OPTION_NAME, $this->written_options );
	}

	/**
	 * Settings the user saved while the database version was still stale are not overwritten.
	 *
	 * @return void
	 */
	public function test_existing_options_are_not_overwritten(): void {
		$saved_by_hand = [ 'comment' => [ 'rule_asb_regexp_active' => '' ] ];

		$this->stub_options(
			[
				'antispam_bee'           => [ 'regexp_check' => 1 ],
				'antispam_bee_options'   => $saved_by_hand,
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertArrayNotHasKey( Settings::OPTION_NAME, $this->written_options );
		$this->assertSame(
			$saved_by_hand,
			$this->stored_options[ Settings::OPTION_NAME ],
			'The stored settings are left exactly as the user saved them.'
		);
	}

	/**
	 * A retry the user asked for clears the stored settings, so the migration really runs.
	 *
	 * Without this the guard that protects the automatic retries would make the manual one
	 * skip its work and report success, which is the opposite of what the button promises.
	 *
	 * @return void
	 */
	public function test_resetting_for_a_retry_clears_the_stored_settings(): void {
		$this->stub_options(
			[
				'antispam_bee'                   => [ 'regexp_check' => 1 ],
				'antispam_bee_options'           => [ 'comment' => [ 'rule_asb_regexp_active' => '' ] ],
				'antispambee_db_version'         => '1.02',
				'antispambee_db_update_failures' => [
					'version'  => '3.0.0-beta.1',
					'attempts' => PluginUpdate::MAX_UPDATE_ATTEMPTS,
					'message'  => 'Migration exploded',
					'time'     => 1,
				],
			]
		);

		PluginUpdate::reset_for_retry();

		$this->assertContains( Settings::OPTION_NAME, $this->deleted_options );
		$this->assertContains( PluginUpdate::FAILURE_OPTION_NAME, $this->deleted_options );

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			'on',
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_regexp_active'],
			'The legacy settings are migrated again instead of being skipped.'
		);
		$this->assertSame( '3.0.0-beta.1', $this->written_options[ PluginUpdate::DB_VERSION_OPTION_NAME ] );
	}

	/**
	 * Marking the database as migrated stops the retries without touching the settings.
	 *
	 * @return void
	 */
	public function test_marking_as_migrated_stops_the_retries(): void {
		$saved_by_hand = [ 'comment' => [ 'rule_asb_regexp_active' => '' ] ];

		$this->stub_options(
			[
				'antispam_bee'                   => [ 'regexp_check' => 1 ],
				'antispam_bee_options'           => $saved_by_hand,
				'antispambee_db_version'         => '1.02',
				'antispambee_db_update_failures' => [
					'version'  => '3.0.0-beta.1',
					'attempts' => PluginUpdate::MAX_UPDATE_ATTEMPTS,
					'message'  => 'Migration exploded',
					'time'     => 1,
				],
			]
		);

		PluginUpdate::mark_as_migrated();

		$this->assertContains( PluginUpdate::FAILURE_OPTION_NAME, $this->deleted_options );
		$this->assertSame( '3.0.0-beta.1', $this->written_options[ PluginUpdate::DB_VERSION_OPTION_NAME ] );
		$this->assertSame(
			$saved_by_hand,
			$this->stored_options[ Settings::OPTION_NAME ],
			'The settings the user configured by hand are left untouched.'
		);
	}

	/**
	 * A stored value that is not an array is corrupt, not a configuration, and is replaced.
	 *
	 * @return void
	 */
	public function test_corrupt_existing_options_are_replaced(): void {
		$this->stub_options(
			[
				'antispam_bee'           => [ 'regexp_check' => 1 ],
				'antispam_bee_options'   => 'corrupted',
				'antispambee_db_version' => '1.02',
			]
		);

		PluginUpdate::maybe_run_plugin_updated_logic();

		$this->assertSame(
			'on',
			$this->written_options[ Settings::OPTION_NAME ]['comment']['rule_asb_regexp_active']
		);
	}
}
