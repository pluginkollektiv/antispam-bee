<?php
/**
 * Integration tests for reporting a migration that completed.
 *
 * @package AntispamBee\Tests\Integration\Admin
 */

namespace AntispamBee\Tests\Integration\Admin;

use AntispamBee\Admin\MigrationFailureNotice;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionClass;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * A migration usually completes in a request nobody is watching, so the report is
 * asserted against a real migration rather than against a hand-written option.
 */
final class MigrationFailureNoticeTest extends TestCase {

	/**
	 * Reset the plugin state that outlives a single request, and act as an administrator.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->reset_plugin_update_state();
		delete_option( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME );
		delete_option( PluginUpdate::FAILURE_OPTION_NAME );
		delete_option( Settings::OPTION_NAME );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Reset again so a failing test cannot poison the next one.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->reset_plugin_update_state();
		delete_option( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME );

		parent::tear_down();
	}

	/**
	 * A migration that carried settings over says so, and says it only once.
	 *
	 * @return void
	 */
	public function test_a_completed_migration_is_reported_once(): void {
		update_option( 'antispam_bee', [ 'regexp_check' => 1 ] );
		update_option( PluginUpdate::DB_VERSION_OPTION_NAME, '1.02' );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertStringContainsString(
			'migrated your settings',
			$this->render(),
			'The migration ran in a request nobody was watching, so the next admin page has to report it.'
		);
		self::assertSame(
			'',
			$this->render(),
			'Reporting it again on every later page load would be noise.'
		);
	}

	/**
	 * A fresh install migrated nothing, so it must not be told that it did.
	 *
	 * @return void
	 */
	public function test_a_fresh_install_is_not_reported_as_migrated(): void {
		delete_option( 'antispam_bee' );
		delete_option( PluginUpdate::DB_VERSION_OPTION_NAME );

		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame( '', $this->render() );
	}

	/**
	 * Render the notice and return what it printed.
	 *
	 * @return string The rendered notice.
	 */
	private function render(): string {
		ob_start();
		MigrationFailureNotice::render();

		return (string) ob_get_clean();
	}

	/**
	 * Clear the per-request guards so the next call migrates again.
	 *
	 * @return void
	 */
	private function reset_plugin_update_state(): void {
		$reflection = new ReflectionClass( PluginUpdate::class );

		foreach ( [ 'db_update_triggered' => false, 'db_version_is_current' => null ] as $name => $value ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}

		wp_cache_delete( Settings::OPTION_NAME );
	}
}
