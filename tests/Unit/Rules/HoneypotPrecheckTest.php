<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

/**
 * Unit tests for how {@see Honeypot::precheck()} treats a submission without
 * the secret comment field.
 *
 * That is the shape of a bot posting the fields core expects straight to
 * `wp-comments-post.php`, and the honeypot is the only final rule that catches
 * it. These cases are kept apart, and named after that bot, so a change that
 * lets such a submission through cannot hide in a larger test.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class HoneypotPrecheckTest extends TestCase {

	/**
	 * What a bot sends when it never uses the rendered form.
	 */
	private const BARE_POST = [
		'comment'         => 'Your point of view caught my eye and was very interesting.',
		'author'          => 'Registrera',
		'email'           => 'bot@example.com',
		'url'             => 'https://example.com/register?ref=ABC',
		'comment_post_ID' => '433',
	];

	protected function set_up(): void {
		parent::set_up();

		stubs(
			[
				'esc_url_raw'  => function ( string $url ) {
					return $url;
				},
				'is_feed'      => false,
				'is_trackback' => false,
				'wp_parse_url' => 'parse_url',
				'wp_unslash'   => function ( $value ) {
					return $value;
				},
			]
		);

		$_SERVER['SCRIPT_NAME'] = '/wp-comments-post.php';
	}

	/**
	 * Mock the helper so the last rendered form did or did not carry the honeypot.
	 *
	 * @param bool $observed Whether the honeypot was seen in the last rendered form.
	 */
	private function injection_observed( bool $observed ): void {
		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->allows( 'get_secret_name_for_post' )->andReturns( 'd7dcf95a06' );
		$honeypot_helper->allows( 'injection_observed' )->andReturns( $observed );
		// Only a submission the honeypot cannot judge is noted for the admin.
		$honeypot_helper->expects( 'record_unguarded_submission' )->times( $observed ? 0 : 1 );
	}

	public function test_a_bare_post_is_an_invalid_request_once_the_form_carried_the_honeypot(): void {
		$this->injection_observed( true );
		$_POST = self::BARE_POST;

		Honeypot::precheck();

		self::assertSame(
			1,
			$_POST['ab_spam__invalid_request'] ?? null,
			'a submission without the secret field must be rejected as an invalid request'
		);
	}

	/**
	 * Fields a bot might add from an older or foreign form must not change that.
	 */
	public function test_a_bare_post_with_extra_fields_is_still_an_invalid_request(): void {
		$this->injection_observed( true );
		$_POST = self::BARE_POST + [
			'm4rk3rf13'    => '1',
			'ab_init_time' => '1',
			'submit'       => 'Post Comment',
		];

		Honeypot::precheck();

		self::assertSame( 1, $_POST['ab_spam__invalid_request'] ?? null );
	}

	/**
	 * A theme the honeypot cannot reach sends genuine comments in this shape too.
	 */
	public function test_a_bare_post_draws_no_verdict_while_the_injection_fails(): void {
		$this->injection_observed( false );
		$_POST = self::BARE_POST;

		Honeypot::precheck();

		self::assertSame( self::BARE_POST, $_POST, 'the honeypot must not judge a form it could not reach' );
	}
}
