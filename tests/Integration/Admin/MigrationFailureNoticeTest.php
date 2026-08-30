<?php
/**
 * Integration tests for the notice that reports on the v2 to v3 migration.
 *
 * @package AntispamBee\Tests\Integration\Admin
 */

namespace AntispamBee\Tests\Integration\Admin;

use AntispamBee\Admin\MigrationFailureNotice;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use ReflectionClass;
use RuntimeException;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * A migration whose steps always fail, standing in for whatever makes a real site's
 * migration blow up. `PluginUpdate` reaches its steps through `static::`, so overriding
 * them here drives the failure path end to end.
 */
final class FailingPluginUpdate extends PluginUpdate {

	/**
	 * Fail before anything is migrated.
	 *
	 * @param string $version_from_db The database revision the install is on.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Always.
	 */
	protected static function run_migration_steps( string $version_from_db ): void {
		throw new RuntimeException( 'A migration step failed at revision ' . $version_from_db );
	}
}

/**
 * What the user is told about their migration.
 *
 * These walk the sequences a user actually goes through — a migration that keeps
 * failing, a retry, settings saved by hand, giving up — because every bug this notice
 * has had so far lived in the transitions between those states rather than in any one
 * of them.
 */
final class MigrationFailureNoticeTest extends TestCase {

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
	 * Act as an administrator, since the notice renders for nobody else.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_request_state();
	}

	/**
	 * Leave no state behind for the next test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->reset_request_state();

		delete_option( PluginUpdate::FAILURE_OPTION_NAME );
		delete_option( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME );

		parent::tear_down();
	}

	/**
	 * A migration that keeps failing ends up asking the user what to do.
	 *
	 * @return void
	 */
	public function test_a_migration_that_gives_up_asks_the_user(): void {
		$this->seed_legacy_install();
		$this->exhaust_the_attempts();

		$output = $this->render();

		self::assertStringContainsString(
			'could not migrate your settings',
			$output,
			'Once the attempts are spent the user has to be told.'
		);
		self::assertStringContainsString(
			'Migrate again',
			$output,
			'With no settings of their own the user is offered a plain retry.'
		);
	}

	/**
	 * A retry the user asked for reports back even when it fails again.
	 *
	 * This is the case that used to be silent: the retry clears the attempts, so the
	 * failure that follows sits far below the cap and rendered nothing at all — leaving
	 * the button looking like it had worked.
	 *
	 * @return void
	 */
	public function test_a_retry_that_fails_again_is_reported(): void {
		$this->seed_legacy_install();
		$this->exhaust_the_attempts();

		// What `handle_retry()` does before redirecting back with the marker.
		PluginUpdate::reset_for_retry();

		// The redirected request: the migration runs again, and fails again.
		$this->reset_request_state();
		FailingPluginUpdate::maybe_run_plugin_updated_logic();

		$_GET[ MigrationFailureNotice::RETRY_RESULT_ARG ] = '1';

		$state = PluginUpdate::get_failure_state();

		self::assertLessThan(
			PluginUpdate::MAX_UPDATE_ATTEMPTS,
			$state['attempts'],
			'The retry has to be reported from below the cap, or this proves nothing.'
		);
		self::assertStringContainsString(
			'could not migrate your settings',
			$this->render(),
			'A retry the user asked for has to report its failure immediately.'
		);
	}

	/**
	 * Without the marker the same failed retry stays quiet, because the automatic
	 * retries are still running and there is nothing to ask the user for yet.
	 *
	 * @return void
	 */
	public function test_an_automatic_attempt_below_the_cap_stays_quiet(): void {
		$this->seed_legacy_install();

		FailingPluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame( '', $this->render() );
	}

	/**
	 * Settings saved by hand change what the retry button promises.
	 *
	 * @return void
	 */
	public function test_settings_saved_by_hand_change_the_offer(): void {
		$this->seed_legacy_install();
		$this->exhaust_the_attempts();
		$this->save_settings_by_hand();

		self::assertStringContainsString(
			'Discard the current settings and migrate again',
			$this->render(),
			'A retry can only restore the old settings by replacing the ones stored now.'
		);
	}

	/**
	 * Case A: the user discards their own settings, and this time the migration works.
	 *
	 * @return void
	 */
	public function test_a_retry_that_succeeds_reports_the_migrated_settings(): void {
		$this->seed_legacy_install();
		$this->exhaust_the_attempts();
		$this->save_settings_by_hand();

		// What `handle_retry()` does: the settings standing in the way have to go.
		PluginUpdate::reset_for_retry();

		// The redirected request, this time with a migration that works.
		$this->reset_request_state();
		PluginUpdate::maybe_run_plugin_updated_logic();

		$output = $this->render();

		self::assertStringContainsString(
			'migrated your settings',
			$output,
			'A migration that finally worked has to say so.'
		);
		self::assertStringNotContainsString(
			'could not migrate',
			$output,
			'The failure must not linger once the migration succeeded.'
		);
		self::assertSame(
			4711,
			get_option( Settings::OPTION_NAME )['spam_count'],
			'The retry has to migrate from the legacy option, not from the hand-made settings.'
		);
		self::assertSame(
			'',
			$this->render(),
			'The success is reported once, not on every page load afterwards.'
		);
	}

	/**
	 * Case B: the user keeps the settings they made, and the notice goes for good.
	 *
	 * @return void
	 */
	public function test_keeping_the_current_settings_ends_the_notice(): void {
		$this->seed_legacy_install();
		$this->exhaust_the_attempts();
		$this->save_settings_by_hand();

		// What `handle_dismiss()` does.
		PluginUpdate::mark_as_migrated();

		$this->reset_request_state();
		PluginUpdate::maybe_run_plugin_updated_logic();

		self::assertSame(
			'',
			$this->render(),
			'Having chosen to keep their settings, the user must not be asked again.'
		);
		self::assertSame(
			[ 'spam_count' => 1 ],
			get_option( Settings::OPTION_NAME ),
			'Keeping the current settings must not overwrite them.'
		);
		self::assertNotFalse(
			get_option( self::LEGACY_OPTION ),
			'The legacy option stays until it is removed deliberately - see #744.'
		);
	}

	/**
	 * Render the notice and return what it printed.
	 *
	 * @return string The rendered output.
	 */
	private function render(): string {
		ob_start();
		MigrationFailureNotice::render();

		return (string) ob_get_clean();
	}

	/**
	 * Run the failing migration until the attempts are spent.
	 *
	 * @return void
	 */
	private function exhaust_the_attempts(): void {
		for ( $attempt = 0; $attempt < PluginUpdate::MAX_UPDATE_ATTEMPTS; $attempt++ ) {
			$this->reset_request_state();
			FailingPluginUpdate::maybe_run_plugin_updated_logic();
		}
	}

	/**
	 * Store settings the way a user would who gave up and configured the plugin by hand.
	 *
	 * @return void
	 */
	private function save_settings_by_hand(): void {
		update_option( Settings::OPTION_NAME, [ 'spam_count' => 1 ] );
		wp_cache_delete( Settings::OPTION_NAME );
	}

	/**
	 * Store a legacy install: the v2 option array plus the revision it was left at.
	 *
	 * @return void
	 */
	private function seed_legacy_install(): void {
		update_option( self::LEGACY_OPTION, [ 'spam_count' => 4711 ] );
		update_option( self::DB_VERSION_OPTION, '1.02' );

		delete_option( Settings::OPTION_NAME );
		delete_option( PluginUpdate::FAILURE_OPTION_NAME );
		delete_option( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME );
		wp_cache_delete( Settings::OPTION_NAME );
	}

	/**
	 * Start a fresh request: clear the per-request guards and the retry marker.
	 *
	 * @return void
	 */
	private function reset_request_state(): void {
		unset( $_GET[ MigrationFailureNotice::RETRY_RESULT_ARG ] );

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
