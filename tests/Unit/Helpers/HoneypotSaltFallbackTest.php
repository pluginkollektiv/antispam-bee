<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Covers seeding the honeypot salt when `NONCE_SALT` cannot be used.
 *
 * This file deliberately does not define `NONCE_SALT`, and the test runs in its
 * own process so the definition another test file makes does not leak into it.
 *
 * {@see HoneypotSaltPlaceholderTest} covers the other way into this branch, a
 * `NONCE_SALT` that is set but does not look generated. The two cases need
 * separate files because they differ only in the value of a constant.
 */
class HoneypotSaltFallbackTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_salt_is_generated_when_nonce_salt_is_unavailable(): void {
		if ( defined( 'NONCE_SALT' ) ) {
			self::markTestSkipped( 'NONCE_SALT is already defined in this process.' );
		}

		$expected_salt = substr( sha1( 'generated-salt' ), 0, 10 );

		when( 'get_option' )->justReturn( '' );
		when( 'wp_generate_password' )->justReturn( 'generated-salt' );

		expect( 'add_option' )
			->once()
			->with( Honeypot::SALT_OPTION, $expected_salt )
			->andReturn( true );

		self::assertSame(
			Honeypot::ensure_secret_starts_with_letter(
				substr( sha1( md5( 'comment-id' . $expected_salt ) ), 0, 10 )
			),
			Honeypot::get_secret_name_for_post(),
			'Without a usable NONCE_SALT the stored salt should come from wp_salt()'
		);
	}
}
