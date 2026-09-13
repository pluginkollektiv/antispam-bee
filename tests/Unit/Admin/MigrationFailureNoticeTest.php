<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\MigrationFailureNotice;
use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Helpers\Settings;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Unit tests for the notice that reports a migration which failed.
 *
 * Every bug this notice has had so far was a question of which state it decided to
 * describe, and what it then claimed about that state, so the assertions here are
 * about the rendered copy rather than about the markup around it.
 */
class MigrationFailureNoticeTest extends TestCase {

	/**
	 * The plugin version the failure state is recorded against.
	 *
	 * @var string
	 */
	private const VERSION = '3.0.0-beta.1';

	/**
	 * The simulated option store.
	 *
	 * @var array<string, mixed>
	 */
	private $stored_options = [];

	/**
	 * Stub the WordPress functions the notice reaches for.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->stored_options = [];

		// `__()` and `esc_html__()` already exist as test stubs; these two do not.
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();

		when( 'current_user_can' )->justReturn( true );
		when( 'get_file_data' )->justReturn( [ 'Version' => self::VERSION ] );
		when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.com/wp-admin/' . $path;
			}
		);
		when( 'wp_nonce_url' )->alias(
			static function ( $url ) {
				return $url . '&_wpnonce=nonce';
			}
		);
		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return array_key_exists( $name, $this->stored_options ) ? $this->stored_options[ $name ] : $default;
			}
		);
	}

	/**
	 * Record a failure state for the current plugin version.
	 *
	 * @param int $attempts How many attempts were spent.
	 *
	 * @return void
	 */
	private function record_failure( int $attempts ): void {
		$this->stored_options[ PluginUpdate::FAILURE_OPTION_NAME ] = [
			'version'  => self::VERSION,
			'attempts' => $attempts,
			'message'  => 'Migration exploded',
			'time'     => 1,
		];
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
	 * An install with nothing wrong has no notice to show.
	 *
	 * @return void
	 */
	public function test_nothing_is_rendered_without_a_recorded_failure(): void {
		$this->assertSame( '', $this->render() );
	}

	/**
	 * A failure is reported from the first attempt, not only once they are spent.
	 *
	 * A retry the user asked for lands on attempt 1, so gating the notice on the cap
	 * made the button look like it had done nothing at all.
	 *
	 * @return void
	 */
	public function test_the_first_failure_is_reported_immediately(): void {
		$this->record_failure( 1 );

		$output = $this->render();

		$this->assertStringContainsString( 'could not migrate your settings yet', $output );
		$this->assertStringContainsString( 'Attempt 1 of 3', $output );
		$this->assertStringContainsString( 'Migration exploded', $output );
	}

	/**
	 * While attempts remain, the notice says another one is coming.
	 *
	 * @return void
	 */
	public function test_a_retry_that_is_still_coming_is_announced(): void {
		$this->record_failure( 2 );

		$output = $this->render();

		$this->assertStringContainsString( 'will try again on the next page load', $output );
		$this->assertStringContainsString( 'Attempt 2 of 3', $output );
		$this->assertStringNotContainsString( 'stopped after', $output );
	}

	/**
	 * Once the attempts are spent, the notice must not promise another one.
	 *
	 * The give-up state used to carry "will be applied as soon as the migration
	 * succeeds", which described a retry that was never going to happen.
	 *
	 * @return void
	 */
	public function test_the_give_up_state_does_not_promise_a_retry(): void {
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );

		$output = $this->render();

		$this->assertStringContainsString( 'stopped after 3 attempts', $output );
		$this->assertStringContainsString( 'will not try again on its own', $output );
		$this->assertStringNotContainsString( 'as soon as the migration succeeds', $output );
		$this->assertStringNotContainsString( 'will try again on the next page load', $output );
	}

	/**
	 * Both ways out are offered in every state the notice is shown in.
	 *
	 * @return void
	 */
	public function test_both_actions_are_offered(): void {
		$this->record_failure( 1 );

		$output = $this->render();

		$this->assertStringContainsString( MigrationFailureNotice::RETRY_ACTION, $output );
		$this->assertStringContainsString( MigrationFailureNotice::DISMISS_ACTION, $output );
	}

	/**
	 * With settings stored, the retry has to say that it replaces them.
	 *
	 * @return void
	 */
	public function test_the_retry_warns_when_it_would_replace_stored_settings(): void {
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );
		$this->stored_options[ Settings::OPTION_NAME ] = [ 'comment' => [ 'rule_asb_regexp_active' => '' ] ];

		$output = $this->render();

		$this->assertStringContainsString( 'Discard the current settings and migrate again', $output );
		$this->assertStringContainsString( 'running with the settings currently stored', $output );
		$this->assertStringNotContainsString( 'running with its default settings', $output );
	}

	/**
	 * Without stored settings there is nothing to discard, so the label must not say so.
	 *
	 * @return void
	 */
	public function test_the_retry_does_not_warn_when_there_is_nothing_to_discard(): void {
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );

		$output = $this->render();

		$this->assertStringContainsString( 'Migrate again', $output );
		$this->assertStringNotContainsString( 'Discard the current settings', $output );
		$this->assertStringContainsString( 'running with its default settings', $output );
	}

	/**
	 * A failure recorded against another plugin version is stale and says nothing.
	 *
	 * @return void
	 */
	public function test_a_failure_from_another_version_is_not_reported(): void {
		$this->stored_options[ PluginUpdate::FAILURE_OPTION_NAME ] = [
			'version'  => '3.0.0-beta.0',
			'attempts' => PluginUpdate::MAX_UPDATE_ATTEMPTS,
			'message'  => 'Migration exploded',
			'time'     => 1,
		];

		$this->assertSame( '', $this->render() );
	}

	/**
	 * Someone who cannot change settings is not shown the notice.
	 *
	 * @return void
	 */
	public function test_the_notice_is_limited_to_users_who_can_act_on_it(): void {
		when( 'current_user_can' )->justReturn( false );
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );

		$this->assertSame( '', $this->render() );
	}
}
