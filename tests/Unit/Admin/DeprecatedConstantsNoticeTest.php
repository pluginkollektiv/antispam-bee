<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\DeprecatedConstantsNotice;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see DeprecatedConstantsNotice}.
 *
 * The constants it checks for cannot be undefined once defined, so each case that defines
 * one runs in its own process.
 */
class DeprecatedConstantsNoticeTest extends TestCase {

	private function mock_dependencies( bool $can_manage = true ): void {
		when( 'current_user_can' )->justReturn( $can_manage );
		when( 'esc_html' )->returnArg();
		when( 'wp_kses' )->alias(
			static function ( $string ) {
				return $string;
			}
		);
	}

	private function render_for( string $hook_suffix ): string {
		ob_start();
		DeprecatedConstantsNotice::maybe_render( $hook_suffix );

		return (string) ob_get_clean();
	}

	public function test_does_not_render_without_a_deprecated_constant(): void {
		self::mock_dependencies();

		self::assertSame( '', self::render_for( 'settings_page_antispam_bee' ) );
	}

	public function test_does_not_render_on_other_admin_pages(): void {
		self::mock_dependencies();

		self::assertSame( '', self::render_for( 'plugins.php' ) );
	}

	public function test_does_not_render_without_manage_options_capability(): void {
		self::mock_dependencies( false );

		self::assertSame( '', self::render_for( 'settings_page_antispam_bee' ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_renders_for_antispam_bee_log_file(): void {
		define( 'ANTISPAM_BEE_LOG_FILE', '/tmp/asb-spam.log' );

		self::mock_dependencies();

		$output = self::render_for( 'settings_page_antispam_bee' );

		self::assertStringContainsString( 'ANTISPAM_BEE_LOG_FILE', $output );
		self::assertStringContainsString( 'ANTISPAM_BEE_SPAM_LOG', $output );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_renders_for_antispam_bee_debug_mode_enabled(): void {
		define( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED', true );

		self::mock_dependencies();

		$output = self::render_for( 'settings_page_antispam_bee' );

		self::assertStringContainsString( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED', $output );
		self::assertStringContainsString( 'ANTISPAM_BEE_DEBUG_LOG', $output );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_does_not_render_when_the_constant_is_falsy(): void {
		define( 'ANTISPAM_BEE_LOG_FILE', false );

		self::mock_dependencies();

		self::assertSame( '', self::render_for( 'settings_page_antispam_bee' ) );
	}
}
