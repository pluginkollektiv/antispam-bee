<?php

namespace AntispamBee\Tests\Unit\Admin;

use AntispamBee\Admin\SiteHealth;
use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see SiteHealth} and the {@see \AntispamBee\Admin\HoneypotStatus}
 * it reports.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SiteHealthTest extends TestCase {

	/**
	 * Stub the plugin settings and the stored honeypot state.
	 *
	 * @param bool                 $active        Whether the honeypot rule is active.
	 * @param bool                 $output_buffer Whether output buffering is enabled.
	 * @param array<string, mixed> $options       Stored options, by name.
	 */
	private function stub_state( bool $active, bool $output_buffer, array $options ): void {
		$settings = mock( 'alias:' . \AntispamBee\Helpers\Settings::class );
		$settings->allows( 'get_option' )->andReturnUsing(
			static function ( $name ) use ( $active, $output_buffer ) {
				if ( 'rule_asb_honeypot_active' === $name ) {
					return $active ? 'on' : null;
				}

				return 'rule_asb_honeypot_output_buffer' === $name && $output_buffer ? 'on' : null;
			}
		);

		when( 'get_option' )->alias(
			static function ( $name ) use ( $options ) {
				return $options[ $name ] ?? false;
			}
		);
		when( 'esc_html' )->returnArg();
		when( 'esc_url' )->returnArg();
		when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.com/wp-admin/' . $path;
			}
		);
	}

	public function test_the_test_is_registered_while_the_rule_is_active(): void {
		$this->stub_state( true, false, [] );

		$tests = SiteHealth::add_tests( [ 'direct' => [] ] );

		self::assertSame( [ SiteHealth::class, 'test_honeypot' ], $tests['direct'][ SiteHealth::HONEYPOT_TEST ]['test'] );
	}

	public function test_the_test_is_left_out_while_the_rule_is_off(): void {
		$this->stub_state( false, false, [ Honeypot::INJECTION_STATE_OPTION => Honeypot::INJECTION_STATE_FAILED ] );

		self::assertSame( [ 'direct' => [] ], SiteHealth::add_tests( [ 'direct' => [] ] ) );
	}

	public function test_a_failed_injection_is_recommended_to_fix(): void {
		$this->stub_state( true, false, [ Honeypot::INJECTION_STATE_OPTION => Honeypot::INJECTION_STATE_FAILED ] );

		$result = SiteHealth::test_honeypot();

		self::assertSame( 'recommended', $result['status'] );
		self::assertStringContainsString( 'could not add its honeypot', $result['description'] );
		self::assertStringContainsString( 'Enable “Inject through output buffering”', $result['description'] );
		self::assertStringContainsString( 'options-general.php?page=antispam_bee&tab=comment', $result['actions'] );
	}

	/**
	 * A theme that builds its own form never reaches the filter, so nothing is
	 * recorded as failed; comments arriving without the honeypot are the sign.
	 */
	public function test_comments_without_any_guarded_form_are_recommended_to_fix(): void {
		$this->stub_state( true, false, [ Honeypot::UNGUARDED_SUBMISSION_OPTION => 1727000000 ] );

		$result = SiteHealth::test_honeypot();

		self::assertSame( 'recommended', $result['status'] );
		self::assertStringContainsString( 'no comment form displayed so far carried', $result['description'] );
	}

	public function test_the_advice_changes_once_output_buffering_is_on(): void {
		$this->stub_state( true, true, [ Honeypot::INJECTION_STATE_OPTION => Honeypot::INJECTION_STATE_FAILED ] );

		$result = SiteHealth::test_honeypot();

		self::assertStringContainsString( 'is already enabled', $result['description'] );
		self::assertStringNotContainsString( 'Enable “Inject through output buffering”', $result['description'] );
	}

	public function test_a_form_carrying_the_honeypot_is_good(): void {
		$this->stub_state( true, false, [ Honeypot::INJECTION_STATE_OPTION => Honeypot::INJECTION_STATE_INJECTED ] );

		$result = SiteHealth::test_honeypot();

		self::assertSame( 'good', $result['status'] );
		self::assertSame( 'The Antispam Bee honeypot is in your comment form', $result['label'] );
	}

	public function test_nothing_to_check_yet_is_good(): void {
		$this->stub_state( true, false, [] );

		$result = SiteHealth::test_honeypot();

		self::assertSame( 'good', $result['status'] );
		self::assertSame( 'No comment form has been checked for the Antispam Bee honeypot yet', $result['label'] );
	}
}
