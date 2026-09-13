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
 * Stands in for the `exit` that follows the redirect, so a handler can be run to
 * completion in-process and the target asserted.
 */
class RedirectedException extends \RuntimeException {

	/**
	 * The location the handler redirected to.
	 *
	 * @var string
	 */
	public $location;

	/**
	 * @param string $location The redirect target.
	 */
	public function __construct( string $location ) {
		parent::__construct( 'Redirected to ' . $location );

		$this->location = $location;
	}
}

/**
 * Stands in for the `wp_die()` that ends an unauthorised request.
 */
class DiedException extends \RuntimeException {
}

/**
 * Stands in for the `die()` inside `check_admin_referer()` on a bad nonce.
 */
class BadNonceException extends \RuntimeException {
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
	 * Options removed via `delete_option()`.
	 *
	 * @var string[]
	 */
	private $deleted_options = [];

	/**
	 * Options written via `update_option()`, keyed by option name.
	 *
	 * @var array<string, mixed>
	 */
	private $written_options = [];

	/**
	 * Nonce actions passed to `check_admin_referer()`.
	 *
	 * @var string[]
	 */
	private $checked_nonces = [];

	/**
	 * Whether the nonce check should fail.
	 *
	 * @var bool
	 */
	private $nonce_is_valid = true;

	/**
	 * Stub the WordPress functions the notice reaches for.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->stored_options  = [];
		$this->deleted_options = [];
		$this->written_options = [];
		$this->checked_nonces  = [];
		$this->nonce_is_valid  = true;

		// `__()` and `esc_html__()` already exist as test stubs; these two do not.
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();

		when( 'current_user_can' )->justReturn( true );
		when( 'is_network_admin' )->justReturn( false );
		when( 'get_bloginfo' )->justReturn( 'Example Subsite' );
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
		when( 'delete_option' )->alias(
			function ( $name ) {
				$this->deleted_options[] = $name;
				unset( $this->stored_options[ $name ] );

				return true;
			}
		);
		when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->written_options[ $name ] = $value;
				$this->stored_options[ $name ]  = $value;

				return true;
			}
		);
		when( 'wp_get_referer' )->justReturn( 'https://example.com/wp-admin/options-general.php' );

		/*
		 * The handlers end in `exit`, and `check_admin_referer()` and `wp_die()` end the
		 * request themselves. Throwing from each stub stops the handler exactly where the
		 * real call would, which is also what makes "nothing destructive ran" assertable:
		 * had the handler continued past an authorisation failure, the options would show it.
		 */
		when( 'wp_safe_redirect' )->alias(
			static function ( $location ) {
				throw new RedirectedException( (string) $location );
			}
		);
		when( 'wp_die' )->alias(
			static function () {
				throw new DiedException( 'wp_die' );
			}
		);
		when( 'check_admin_referer' )->alias(
			function ( $action ) {
				$this->checked_nonces[] = $action;

				if ( ! $this->nonce_is_valid ) {
					throw new BadNonceException( 'bad nonce' );
				}

				return true;
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
		$this->assertStringContainsString( 'Attempt 1 of ' . PluginUpdate::MAX_UPDATE_ATTEMPTS, $output );
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
		$this->assertStringContainsString( 'Attempt 2 of ' . PluginUpdate::MAX_UPDATE_ATTEMPTS, $output );
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

		$this->assertStringContainsString(
			sprintf( 'stopped after %d attempts', PluginUpdate::MAX_UPDATE_ATTEMPTS ),
			$output
		);
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

	/**
	 * On the network screens the notice has to say which site it is about.
	 *
	 * The migration belongs to one site, so an unqualified notice there would read as a
	 * statement about the whole network.
	 *
	 * @return void
	 */
	public function test_the_network_screens_name_the_site_the_failure_belongs_to(): void {
		when( 'is_network_admin' )->justReturn( true );
		$this->record_failure( 1 );

		$output = $this->render();

		$this->assertStringContainsString( 'Example Subsite', $output );
		$this->assertStringContainsString( 'migrate separately', $output );
	}

	/**
	 * On a single site there is no other site to distinguish it from.
	 *
	 * @return void
	 */
	public function test_a_single_site_notice_does_not_name_the_site(): void {
		$this->record_failure( 1 );

		$output = $this->render();

		$this->assertStringNotContainsString( 'Example Subsite', $output );
	}

	/**
	 * The notice is registered for the network screens as well as the site ones.
	 *
	 * A network-activated install leaves administrators on the network screens, where
	 * `admin_notices` never fires.
	 *
	 * @return void
	 */
	public function test_the_notice_is_registered_for_both_admin_contexts(): void {
		MigrationFailureNotice::init();

		$this->assertNotFalse(
			has_action( 'admin_notices', [ MigrationFailureNotice::class, 'render' ] )
		);
		$this->assertNotFalse(
			has_action( 'network_admin_notices', [ MigrationFailureNotice::class, 'render' ] )
		);
	}

	/**
	 * The retry clears both the stored settings and the failure state, then goes back.
	 *
	 * @return void
	 */
	public function test_the_retry_clears_the_state_and_redirects_back(): void {
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );
		$this->stored_options[ Settings::OPTION_NAME ] = [ 'comment' => [] ];

		try {
			MigrationFailureNotice::handle_retry();
			$this->fail( 'The handler has to end the request by redirecting.' );
		} catch ( RedirectedException $redirect ) {
			$this->assertSame( 'https://example.com/wp-admin/options-general.php', $redirect->location );
		}

		$this->assertContains( Settings::OPTION_NAME, $this->deleted_options );
		$this->assertContains( PluginUpdate::FAILURE_OPTION_NAME, $this->deleted_options );
		$this->assertSame( [ MigrationFailureNotice::RETRY_ACTION ], $this->checked_nonces );
	}

	/**
	 * Keeping the current settings records the version and leaves the settings alone.
	 *
	 * @return void
	 */
	public function test_the_dismissal_records_the_version_without_touching_the_settings(): void {
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );
		$this->stored_options[ Settings::OPTION_NAME ] = [ 'comment' => [] ];

		try {
			MigrationFailureNotice::handle_dismiss();
			$this->fail( 'The handler has to end the request by redirecting.' );
		} catch ( RedirectedException $redirect ) {
			unset( $redirect );
		}

		$this->assertSame(
			self::VERSION,
			$this->written_options[ PluginUpdate::DB_VERSION_OPTION_NAME ],
			'Recording the version is what actually stops the retries.'
		);
		$this->assertContains( PluginUpdate::FAILURE_OPTION_NAME, $this->deleted_options );
		$this->assertNotContains(
			Settings::OPTION_NAME,
			$this->deleted_options,
			'Keeping the current settings must not delete them.'
		);
	}

	/**
	 * Without the capability, the retry must not reach the deletions.
	 *
	 * `reset_for_retry()` throws the settings away, so this is the difference between a
	 * capability check and data loss for anyone who can be made to follow a link.
	 *
	 * @return void
	 */
	public function test_the_retry_is_refused_without_the_capability(): void {
		when( 'current_user_can' )->justReturn( false );
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );
		$this->stored_options[ Settings::OPTION_NAME ] = [ 'comment' => [] ];

		$this->expectException( DiedException::class );

		try {
			MigrationFailureNotice::handle_retry();
		} finally {
			$this->assertSame( [], $this->deleted_options, 'Nothing may be deleted for a user who may not do this.' );
			$this->assertSame( [], $this->checked_nonces, 'The capability is checked before the nonce.' );
		}
	}

	/**
	 * Without the capability, the dismissal must not record the version either.
	 *
	 * @return void
	 */
	public function test_the_dismissal_is_refused_without_the_capability(): void {
		when( 'current_user_can' )->justReturn( false );
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );

		$this->expectException( DiedException::class );

		try {
			MigrationFailureNotice::handle_dismiss();
		} finally {
			$this->assertSame( [], $this->written_options, 'Nothing may be written for a user who may not do this.' );
			$this->assertSame( [], $this->deleted_options );
		}
	}

	/**
	 * A request that does not carry the nonce must not reach the deletions.
	 *
	 * Both handlers are plain links, so without this they would fire on any request that
	 * can be pointed at an administrator's browser.
	 *
	 * @return void
	 */
	public function test_the_retry_is_refused_without_a_valid_nonce(): void {
		$this->nonce_is_valid = false;
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );
		$this->stored_options[ Settings::OPTION_NAME ] = [ 'comment' => [] ];

		$this->expectException( BadNonceException::class );

		try {
			MigrationFailureNotice::handle_retry();
		} finally {
			$this->assertSame( [], $this->deleted_options, 'A missing nonce must stop the handler before it deletes anything.' );
		}
	}

	/**
	 * The dismissal is nonce-protected too, and against its own action.
	 *
	 * @return void
	 */
	public function test_the_dismissal_is_refused_without_a_valid_nonce(): void {
		$this->nonce_is_valid = false;
		$this->record_failure( PluginUpdate::MAX_UPDATE_ATTEMPTS );

		$this->expectException( BadNonceException::class );

		try {
			MigrationFailureNotice::handle_dismiss();
		} finally {
			$this->assertSame( [ MigrationFailureNotice::DISMISS_ACTION ], $this->checked_nonces );
			$this->assertSame( [], $this->written_options );
		}
	}
}
