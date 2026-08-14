<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Covers a `NONCE_SALT` that is configured but is not a secret.
 *
 * The placeholder in `wp-config-sample.php` is translated in localised
 * WordPress packages, so the value on an unconfigured German install is neither
 * the English `put your unique phrase here` nor anything else that can be
 * enumerated. It is recognised by shape instead: a natural-language phrase
 * contains whitespace, where a generated salt never does.
 *
 * The constant is defined here rather than varied per test, so this needs its
 * own file and its own process.
 */
class HoneypotSaltPlaceholderTest extends TestCase {

	/**
	 * A translated placeholder, as a localised package ships it.
	 *
	 * @var string
	 */
	private const TRANSLATED_PLACEHOLDER = 'Füge hier Deine einzigartige Phrase ein';

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_translated_placeholder_is_not_used_as_a_salt(): void {
		if ( defined( 'NONCE_SALT' ) ) {
			self::markTestSkipped( 'NONCE_SALT is already defined in this process.' );
		}

		define( 'NONCE_SALT', self::TRANSLATED_PLACEHOLDER );

		self::assertGreaterThan(
			32,
			strlen( self::TRANSLATED_PLACEHOLDER ),
			'The placeholder must be long enough that length alone would accept it, so the test proves whitespace is what rejects it'
		);

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
			'A placeholder phrase must be replaced by a generated salt, not hashed as if it were secret'
		);
	}
}
