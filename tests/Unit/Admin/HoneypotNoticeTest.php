<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\HoneypotNotice;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see HoneypotNotice}.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class HoneypotNoticeTest extends TestCase {

	/**
	 * Stub the plugin settings, the stored honeypot state and the current user.
	 *
	 * @param bool                 $active     Whether the honeypot rule is active.
	 * @param array<string, mixed> $options    Stored options, by name.
	 * @param bool                 $can_manage Whether the user may manage options.
	 */
	private function stub_state( bool $active, array $options, bool $can_manage = true ): void {
		$settings = mock( 'alias:' . \AntispamBee\Helpers\Settings::class );
		$settings->allows( 'get_option' )->andReturnUsing(
			static function ( $name ) use ( $active ) {
				return 'rule_asb_honeypot_active' === $name && $active ? 'on' : null;
			}
		);

		when( 'get_option' )->alias(
			static function ( $name ) use ( $options ) {
				return $options[ $name ] ?? false;
			}
		);
		when( 'current_user_can' )->justReturn( $can_manage );
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();
		when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.com/wp-admin/' . $path;
			}
		);
	}

	/**
	 * Render the notice and return its output.
	 *
	 * @return string The rendered markup.
	 */
	private function render(): string {
		ob_start();
		HoneypotNotice::render();

		return (string) ob_get_clean();
	}

	public function test_the_notice_is_only_hooked_on_the_settings_page(): void {
		HoneypotNotice::init();

		self::assertNotFalse( has_action( 'load-settings_page_antispam_bee', [ HoneypotNotice::class, 'register' ] ) );
		self::assertFalse( has_action( 'admin_notices', [ HoneypotNotice::class, 'render' ] ) );

		HoneypotNotice::register();

		self::assertNotFalse( has_action( 'admin_notices', [ HoneypotNotice::class, 'render' ] ) );
	}

	public function test_a_failed_injection_is_reported(): void {
		$this->stub_state( true, [ 'antispam_bee_honeypot_injection' => 'failed' ] );

		$output = $this->render();

		self::assertStringContainsString( 'notice-warning', $output );
		self::assertStringContainsString( 'could not add its honeypot', $output );
		self::assertStringContainsString( 'options-general.php?page=antispam_bee&tab=comment', $output );
	}

	public function test_comments_without_any_guarded_form_are_reported(): void {
		$this->stub_state( true, [ 'antispam_bee_honeypot_unguarded_submission' => 1727000000 ] );

		self::assertStringContainsString( 'no comment form displayed so far carried', $this->render() );
	}

	public function test_nothing_is_shown_while_the_form_carries_the_honeypot(): void {
		$this->stub_state( true, [ 'antispam_bee_honeypot_injection' => 'injected' ] );

		self::assertSame( '', $this->render() );
	}

	public function test_nothing_is_shown_while_the_rule_is_off(): void {
		$this->stub_state( false, [ 'antispam_bee_honeypot_injection' => 'failed' ] );

		self::assertSame( '', $this->render() );
	}

	public function test_nothing_is_shown_to_users_who_cannot_manage_options(): void {
		$this->stub_state( true, [ 'antispam_bee_honeypot_injection' => 'failed' ], false );

		self::assertSame( '', $this->render() );
	}
}
