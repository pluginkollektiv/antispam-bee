<?php
/**
 * Integration tests for activation, deactivation and uninstall.
 *
 * @package AntispamBee\Tests\Integration\Handlers
 */

namespace AntispamBee\Tests\Integration\Handlers;

use AntispamBee\Crons\DeleteSpamCron;
use AntispamBee\Handlers\PluginStateChangeHandler;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionClass;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Uninstall removes user data, so it is asserted against the real options table.
 */
final class PluginStateChangeHandlerTest extends TestCase {

	/**
	 * The legacy option written by Antispam Bee 2.x.
	 *
	 * @var string
	 */
	private const LEGACY_OPTION = 'antispam_bee';

	/**
	 * The option holding the database revision.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'antispambee_db_version';

	/**
	 * Start from a known state.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_plugin_update_state();
	}

	/**
	 * Remove anything the handler may have scheduled or written.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		wp_clear_scheduled_hook( DeleteSpamCron::CRONJOB_NAME );
		$this->reset_plugin_update_state();

		parent::tear_down();
	}

	public function test_uninstall_removes_the_plugin_options_when_enabled(): void {
		$this->seed_install( 'on' );

		PluginStateChangeHandler::uninstall();

		self::assertFalse(
			get_option( Settings::OPTION_NAME ),
			'The plugin options have to be removed on uninstall.'
		);
		self::assertFalse(
			get_option( self::DB_VERSION_OPTION ),
			'The database version has to be removed on uninstall.'
		);
	}

	public function test_uninstall_keeps_the_plugin_options_when_disabled(): void {
		$this->seed_install( '' );

		PluginStateChangeHandler::uninstall();

		self::assertNotFalse(
			get_option( Settings::OPTION_NAME ),
			'The plugin options must survive an uninstall that was not opted into.'
		);
		self::assertNotFalse(
			get_option( self::DB_VERSION_OPTION ),
			'The database version must survive an uninstall that was not opted into.'
		);
	}

	/**
	 * Guards the deferred cleanup tracked in issue #744.
	 *
	 * The two deletions are commented out in `maybe_remove_antispam_bee_data()` on purpose
	 * while v3 is in beta. When they are enabled for the stable 3.0 release, this test
	 * turns red and documents exactly what has to be inverted.
	 *
	 * @return void
	 */
	public function test_uninstall_still_keeps_the_legacy_data(): void {
		$this->seed_install( 'on' );

		$comment_id = self::factory()->comment->create();
		add_comment_meta( $comment_id, 'antispam_bee_reason', 'css' );

		PluginStateChangeHandler::uninstall();

		self::assertNotFalse(
			get_option( self::LEGACY_OPTION ),
			'The legacy option is kept as a safety net until the stable 3.0 release (see #744).'
		);
		self::assertSame(
			'css',
			get_comment_meta( $comment_id, 'antispam_bee_reason', true ),
			'The legacy comment meta is kept until the stable 3.0 release (see #744).'
		);
	}

	public function test_uninstall_removes_the_options_of_every_site(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a multisite installation (WP_MULTISITE=1).' );
		}

		$this->seed_install( 'on' );

		$second_site = self::factory()->blog->create();
		switch_to_blog( $second_site );
		$this->seed_install( 'on' );
		restore_current_blog();

		PluginStateChangeHandler::uninstall();

		switch_to_blog( $second_site );
		$options_of_second_site = get_option( Settings::OPTION_NAME );
		restore_current_blog();

		self::assertFalse(
			get_option( Settings::OPTION_NAME ),
			'The options of the main site have to be removed.'
		);
		self::assertFalse(
			$options_of_second_site,
			'The options of every site of the network have to be removed.'
		);
	}

	public function test_deactivation_unschedules_the_cron_job(): void {
		wp_schedule_event( time(), 'daily', DeleteSpamCron::CRONJOB_NAME );

		PluginStateChangeHandler::deactivate();

		self::assertFalse(
			wp_next_scheduled( DeleteSpamCron::CRONJOB_NAME ),
			'Deactivating the plugin has to unschedule the cron job.'
		);
	}

	/**
	 * Store an install that opted into (or out of) data removal on uninstall.
	 *
	 * @param string $delete_data_on_uninstall Whether to remove the data, `on` or an empty string.
	 *
	 * @return void
	 */
	private function seed_install( string $delete_data_on_uninstall ): void {
		update_option(
			Settings::OPTION_NAME,
			[
				'general' => [
					'general_delete_data_on_uninstall_active' => $delete_data_on_uninstall,
				],
			]
		);
		update_option( self::DB_VERSION_OPTION, '3.0.0-beta.1' );
		update_option( self::LEGACY_OPTION, [ 'regexp_check' => 1 ] );

		wp_cache_delete( Settings::OPTION_NAME );
	}

	/**
	 * Clear the private static caches that make the migration run at most once per request.
	 *
	 * Reading the settings triggers `PluginUpdate::maybe_run_plugin_updated_logic()`, so the
	 * caches leak between tests unless they are reset here as well.
	 *
	 * @return void
	 */
	private function reset_plugin_update_state(): void {
		$reflection = new ReflectionClass( PluginUpdate::class );

		$triggered = $reflection->getProperty( 'db_update_triggered' );
		$triggered->setAccessible( true );
		$triggered->setValue( null, false );

		$is_current = $reflection->getProperty( 'db_version_is_current' );
		$is_current->setAccessible( true );
		$is_current->setValue( null, null );

		wp_cache_delete( Settings::OPTION_NAME );
	}
}
