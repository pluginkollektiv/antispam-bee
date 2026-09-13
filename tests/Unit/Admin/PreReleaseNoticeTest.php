<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\PreReleaseNotice;
use RuntimeException;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\PLUGIN_VERSION' ) ) {
	define( 'AntispamBee\PLUGIN_VERSION', '3.0.0-beta.2' );
}

/**
 * Unit tests for {@see PreReleaseNotice}.
 */
class PreReleaseNoticeTest extends TestCase {

	/**
	 * Stub the WordPress functions the notice depends on.
	 *
	 * @param bool $can_manage Whether the mock user may manage options.
	 * @param bool $dismissed  Whether the mock user dismissed the notice.
	 */
	private function mock_dependencies( bool $can_manage = true, ?string $dismissed_version = null ): void {
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();
		when( 'wp_kses_post' )->returnArg();
		when( 'wp_nonce_url' )->returnArg();
		when( 'admin_url' )->returnArg();
		when( 'current_user_can' )->justReturn( $can_manage );
		when( 'get_current_user_id' )->justReturn( 1 );
		when( 'get_user_meta' )->alias(
			static function ( $id, $key ) use ( $dismissed_version ) {
				if ( $key !== PreReleaseNotice::DISMISSED_META_KEY ) {
					return false;
				}

				return null === $dismissed_version ? '' : $dismissed_version;
			}
		);
		when( 'plugin_dir_url' )->returnArg();
		when( 'wp_create_nonce' )->justReturn( 'nonce' );
	}

	/**
	 * Stub a function that halts the request so the test can record it was reached.
	 *
	 * Patchwork cannot stub `exit` and a stub of `wp_die()` would let the handler
	 * continue past it. This trampoline forwards the arguments the real function
	 * received, then throws a sentinel that {@see self::assert_and_terminates}
	 * catches.
	 *
	 * @param string   $function The halting function name.
	 * @param \Closure $record   Receives the arguments the trampoline was called with.
	 */
	private function stub_terminator( string $function, \Closure $record ): void {
		when( $function )->alias(
			static function ( ...$args ) use ( $record ) {
				$record( ...$args );

				throw new RuntimeException( '__ANTISPAM_BEE_EXPECTED_HALT__' );
			}
		);
	}

	/**
	 * Assert the handler ended the request as expected.
	 *
	 * @param \Closure $run The handler invocation under test.
	 */
	private function assert_and_terminates( \Closure $run ): void {
		try {
			$run();
		} catch ( RuntimeException $e ) {
			if ( '__ANTISPAM_BEE_EXPECTED_HALT__' !== $e->getMessage() ) {
				throw $e;
			}

			return;
		}

		self::fail( 'Expected the handler to end the request' );
	}

	/**
	 * Render the notice and return the buffered output.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 *
	 * @return string Rendered output.
	 */
	private function render_for( string $hook_suffix ): string {
		ob_start();
		PreReleaseNotice::maybe_render( $hook_suffix );

		return (string) ob_get_clean();
	}

	public function test_renders_on_the_settings_page(): void {
		self::mock_dependencies();

		$output = self::render_for( 'settings_page_antispam_bee' );

		self::assertStringContainsString( 'pre-release', $output );
		self::assertStringContainsString( 'is-dismissible', $output );
		self::assertStringContainsString( '<code>3.0.0-beta.2</code>', $output );
		self::assertStringNotContainsString( '<button type="button" class="notice-dismiss"', $output );
	}

	public function test_only_the_dismiss_link_carries_the_dismiss_marker(): void {
		self::mock_dependencies();

		$output = self::render_for( 'plugins.php' );

		self::assertStringContainsString(
			'<a class="button" href="admin-post.php?action=' . PreReleaseNotice::DISMISS_ACTION . '" data-antispam-bee-dismiss>',
			$output,
			'The Dismiss link must carry the marker the click handler matches'
		);

		self::assertMatchesRegularExpression(
			'/<div class="notice[^>]*data-antispam-bee-dismiss-link="[^"]+"/',
			$output,
			'The notice must keep the fallback URL the failed AJAX call navigates to'
		);

		self::assertSame(
			1,
			preg_match(
				'/<a[^>]*href="' . preg_quote( PreReleaseNotice::FEEDBACK_URL, '/' ) . '"[^>]*>/',
				$output,
				$feedback_link
			),
			'The notice must render the feedback link'
		);
		self::assertStringNotContainsString(
			'data-antispam-bee-dismiss',
			$feedback_link[0],
			'The feedback link must not be matched by the dismiss click handler'
		);
	}

	public function test_renders_on_the_plugins_list(): void {
		self::mock_dependencies();

		self::assertStringContainsString( 'pre-release', self::render_for( 'plugins.php' ) );
	}

	public function test_renders_using_the_global_hook_suffix(): void {
		self::mock_dependencies();
		$GLOBALS['hook_suffix'] = 'settings_page_antispam_bee';

		ob_start();
		PreReleaseNotice::admin_notices();

		self::assertStringContainsString( 'pre-release', (string) ob_get_clean() );
	}

	public function test_always_init_registers_the_dismissal_handlers(): void {
		$actions = [];
		when( 'add_action' )->alias(
			static function ( $hook, $callback ) use ( &$actions ) {
				$actions[ $hook ] = $callback;
			}
		);

		PreReleaseNotice::always_init();

		self::assertArrayHasKey(
			'wp_ajax_' . PreReleaseNotice::DISMISS_ACTION,
			$actions,
			'The AJAX dismissal handler must be registered'
		);
		self::assertArrayHasKey(
			'admin_post_' . PreReleaseNotice::DISMISS_ACTION,
			$actions,
			'The no-JavaScript dismissal handler must be registered'
		);
	}

	public function test_does_not_render_on_other_admin_pages(): void {
		self::mock_dependencies();

		self::assertSame( '', self::render_for( 'dashboard' ) );
	}

	public function test_does_not_render_without_manage_options_capability(): void {
		self::mock_dependencies( false );

		self::assertSame( '', self::render_for( 'plugins.php' ) );
	}

	public function test_does_not_render_when_dismissed_for_the_installed_version(): void {
		self::mock_dependencies( true, '3.0.0-beta.2' );

		self::assertSame( '', self::render_for( 'plugins.php' ) );
	}

	public function test_renders_again_for_a_newer_prerelease_version(): void {
		self::mock_dependencies( true, '3.0.0-RC.1' );

		self::assertStringContainsString( 'pre-release', self::render_for( 'plugins.php' ) );
	}

	public function test_enqueues_assets_on_the_plugins_list(): void {
		self::mock_dependencies();
		$enqueued = [];
		when( 'wp_enqueue_script' )->alias(
			static function ( $handle, $src, $deps, $ver, $in_footer ) use ( &$enqueued ) {
				$enqueued[] = $handle;
			}
		);
		when( 'wp_localize_script' )->justReturn( null );
		when( 'wp_create_nonce' )->justReturn( 'nonce' );

		PreReleaseNotice::maybe_enqueue_assets( 'plugins.php' );

		self::assertSame( [ 'antispam-bee-pre-release-notice' ], $enqueued );
	}

	public function test_does_not_enqueue_assets_on_other_admin_pages(): void {
		self::mock_dependencies();
		$enqueued = [];
		when( 'wp_enqueue_script' )->alias(
			static function ( $handle ) use ( &$enqueued ) {
				$enqueued[] = $handle;
			}
		);
		when( 'wp_localize_script' )->justReturn( null );

		PreReleaseNotice::maybe_enqueue_assets( 'dashboard' );

		self::assertSame( [], $enqueued );
	}

	public function test_handle_dismiss_persists_per_user(): void {
		when( 'wp_doing_ajax' )->justReturn( true );
		self::mock_dependencies();
		$updated = [];
		when( 'check_ajax_referer' )->justReturn( 1 );
		when( 'update_user_meta' )->alias(
			static function ( $id, $key, $value ) use ( &$updated ) {
				$updated[] = [ $id, $key, $value ];

				return true;
			}
		);
		$sent = false;
		when( 'wp_send_json_success' )->alias(
			static function () use ( &$sent ) {
				$sent = true;

				throw new RuntimeException( '__ANTISPAM_BEE_EXPECTED_HALT__' );
			}
		);

		self::assert_and_terminates(
			static function () {
				PreReleaseNotice::handle_dismiss();
			}
		);

		self::assertSame( [ [ 1, PreReleaseNotice::DISMISSED_META_KEY, \AntispamBee\PLUGIN_VERSION ] ], $updated );
		self::assertTrue( $sent, 'The AJAX request must be acknowledged' );
	}

	public function test_handle_dismiss_redirects_when_javascript_is_disabled(): void {
		self::mock_dependencies();
		when( 'wp_doing_ajax' )->justReturn( false );
		$updated = [];
		when( 'check_admin_referer' )->justReturn( 1 );
		when( 'update_user_meta' )->alias(
			static function ( $id, $key, $value ) use ( &$updated ) {
				$updated[] = [ $id, $key, $value ];

				return true;
			}
		);
		when( 'wp_get_referer' )->justReturn( 'https://example.com/wp-admin/plugins.php' );
		$target = null;
		when( 'wp_safe_redirect' )->alias(
			static function ( $url ) use ( &$target ) {
				$target = $url;
				throw new RuntimeException( '__ANTISPAM_BEE_EXPECTED_HALT__' );
			}
		);

		self::assert_and_terminates(
			static function () {
				PreReleaseNotice::handle_dismiss();
			}
		);

		self::assertSame( [ [ 1, PreReleaseNotice::DISMISSED_META_KEY, \AntispamBee\PLUGIN_VERSION ] ], $updated );
		self::assertSame( 'https://example.com/wp-admin/plugins.php', $target );
	}

	public function test_handle_dismiss_denies_ajax_without_capability(): void {
		self::mock_dependencies( false );
		when( 'wp_doing_ajax' )->justReturn( true );

		$captured = null;
		when( 'wp_send_json_error' )->alias(
			static function ( ...$args ) use ( &$captured ) {
				$captured = $args;

				throw new RuntimeException( '__ANTISPAM_BEE_EXPECTED_HALT__' );
			}
		);

		self::assert_and_terminates(
			static function () {
				PreReleaseNotice::handle_dismiss();
			}
		);

		self::assertNotNull( $captured, 'The handler must refuse users without the capability' );
		self::assertCount( 2, $captured );
		self::assertSame( 403, $captured[1], 'The AJAX denial must carry an HTTP 403 status' );
	}

	public function test_handle_dismiss_denies_form_request_without_capability(): void {
		self::mock_dependencies( false );
		when( 'wp_doing_ajax' )->justReturn( false );

		$captured = null;
		self::stub_terminator( 'wp_die', static function ( ...$args ) use ( &$captured ) {
			$captured = $args;
		} );

		self::assert_and_terminates(
			static function () {
				PreReleaseNotice::handle_dismiss();
			}
		);

		self::assertNotNull( $captured, 'The handler must refuse users without the capability' );
		self::assertCount( 3, $captured );
		self::assertSame( '', $captured[1], 'The page title must be an empty string' );
		self::assertSame( 403, $captured[2], 'The non-AJAX denial must carry an HTTP 403 status' );
	}

	/**
	 * Data provider for pre-release detection.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public static function data_is_pre_release(): array {
		return [
			'stable 3.0.0'            => [ '3.0.0', false ],
			'stable 1.2.3'            => [ '1.2.3', false ],
			'stable with prefix'      => [ 'v2.11.13', false ],
			'rc suffix'               => [ '3.0.0-RC.1', true ],
			'beta suffix'             => [ '3.0.0-beta.2', true ],
			'alpha suffix'            => [ '3.0.0-alpha', true ],
			'mixed case suffix'       => [ '3.0.0-Rc1', true ],
			'build metadata'          => [ '3.0.0-beta.2+build', true ],
			'empty string'            => [ '', false ],
			'not a version'           => [ 'foo', false ],
			'suffix without number'   => [ '-beta', false ],
		];
	}

	/**
	 * Test is_pre_release() against a range of version strings.
	 *
	 * @param string $version Version string.
	 * @param bool   $expected Expected result.
	 *
	 * @dataProvider data_is_pre_release
	 */
	public function test_is_pre_release( string $version, bool $expected ): void {
		self::assertSame( $expected, PreReleaseNotice::is_pre_release( $version ) );
	}
}
