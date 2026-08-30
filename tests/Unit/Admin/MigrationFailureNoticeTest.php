<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\MigrationFailureNotice;
use AntispamBee\Handlers\PluginUpdate;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

/**
 * Tests for the notice that surfaces a migration which gave up.
 */
class MigrationFailureNoticeTest extends TestCase {

	/**
	 * The simulated option store that `get_option()` reads from.
	 *
	 * @var array<string, mixed>
	 */
	private $stored_options = [];

	/**
	 * Options removed via `delete_option()`.
	 *
	 * @var string[]
	 */
	private $deleted_options = [];

	/**
	 * Stub the WordPress functions the notice depends on.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->stored_options  = [];
		$this->deleted_options = [];

		unset( $_GET[ MigrationFailureNotice::RETRY_RESULT_ARG ] );

		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.1' ] );
		when( 'current_user_can' )->justReturn( true );
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();
		when( 'admin_url' )->returnArg();
		when( 'wp_nonce_url' )->returnArg();
		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return array_key_exists( $name, $this->stored_options ) ? $this->stored_options[ $name ] : $default;
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
	 * Render the notice and return the buffered output.
	 *
	 * @return string Rendered output.
	 */
	private function render(): string {
		ob_start();
		MigrationFailureNotice::render();

		return (string) ob_get_clean();
	}

	/**
	 * Build a failure state for the current plugin version.
	 *
	 * @param int $attempts Number of attempts spent.
	 *
	 * @return array<string, mixed> The failure state.
	 */
	private function failure_state( int $attempts ): array {
		return [
			'version'  => '3.0.0-beta.1',
			'attempts' => $attempts,
			'message'  => 'Migration exploded',
			'time'     => 1,
		];
	}

	/**
	 * While the automatic retries are still running there is nothing to ask the user for.
	 *
	 * @return void
	 */
	public function test_nothing_is_rendered_below_the_cap(): void {
		$this->stored_options = [
			PluginUpdate::FAILURE_OPTION_NAME => $this->failure_state( 1 ),
		];

		$this->assertSame( '', $this->render() );
	}

	/**
	 * A retry the user asked for reports back even though it is far below the cap.
	 *
	 * A manual retry resets the attempts to zero, so the failure that follows sits at one
	 * attempt. Staying silent there makes the button look like it succeeded.
	 *
	 * @return void
	 */
	public function test_a_failed_manual_retry_is_reported_below_the_cap(): void {
		$this->stored_options = [
			PluginUpdate::FAILURE_OPTION_NAME => $this->failure_state( 1 ),
		];

		$_GET[ MigrationFailureNotice::RETRY_RESULT_ARG ] = '1';

		$this->assertStringContainsString( 'could not migrate your settings', $this->render() );
	}

	/**
	 * The marker alone reports nothing when no attempt was recorded.
	 *
	 * @return void
	 */
	public function test_the_retry_marker_alone_reports_nothing(): void {
		$_GET[ MigrationFailureNotice::RETRY_RESULT_ARG ] = '1';

		$this->assertSame( '', $this->render() );
	}

	/**
	 * A migration that completed is reported once and then forgotten.
	 *
	 * @return void
	 */
	public function test_a_completed_migration_is_reported_once(): void {
		$this->stored_options = [
			PluginUpdate::MIGRATION_NOTICE_OPTION_NAME => '1.02',
		];

		$this->assertStringContainsString( 'migrated your settings', $this->render() );
		$this->assertContains( PluginUpdate::MIGRATION_NOTICE_OPTION_NAME, $this->deleted_options );
		$this->assertSame( '', $this->render() );
	}

	/**
	 * The success notice wins over a stale failure state from an earlier attempt.
	 *
	 * @return void
	 */
	public function test_a_completed_migration_outranks_an_earlier_failure(): void {
		$this->stored_options = [
			PluginUpdate::MIGRATION_NOTICE_OPTION_NAME => '1.02',
			PluginUpdate::FAILURE_OPTION_NAME          => $this->failure_state( 3 ),
		];

		$output = $this->render();

		$this->assertStringContainsString( 'migrated your settings', $output );
		$this->assertStringNotContainsString( 'could not migrate', $output );
	}
}
