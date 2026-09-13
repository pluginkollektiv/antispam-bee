<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Honeypot} (helper).
 */
class HoneypotHelperTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_returns_markup_unchanged_when_field_not_found(): void {
		$markup = '<textarea id="other" name="other"></textarea>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertSame( $markup, $result, 'inject() should return the markup unchanged when field id is not found' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_returns_markup_unchanged_when_name_attribute_is_missing(): void {
		$markup = '<textarea id="comment"></textarea>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertSame( $markup, $result, 'inject() should return the markup unchanged when the field has no name attribute' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_does_not_emit_warnings_for_html5_markup(): void {
		$markup = '<article><textarea id="comment" name="comment" required>My Content</textarea></article>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertNotEmpty( $result, 'inject() should handle HTML5 markup' );
		self::assertEmpty( libxml_get_errors(), 'inject() should not leave libxml errors behind' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_with_quoted_attributes(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<textarea id="comment" name="comment" class="my-class">My Content</textarea>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertNotEmpty( $result, 'inject() should return non-empty markup for a valid field' );
		self::assertStringContainsString( 'name="' . $name . '"', $result, 'Secret name should be in the output' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_with_unquoted_attributes(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<textarea id=comment name=comment class="my-class">My Content</textarea>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertNotEmpty( $result, 'inject() should handle textarea with unquoted attributes' );
		self::assertStringContainsString( 'name="' . $name . '"', $result, 'Secret name should be in the output for unquoted markup' );
	}

	/**
	 * Antispam Bee 2.x derived the field names from the raw salt with this exact
	 * chain, so a site upgrading keeps the names it already rendered — including
	 * the ones sitting in a page cache. Hashing or shortening the salt first would
	 * rename every field once, on upgrade, for every site.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_secret_is_derived_the_way_version_2_derived_it(): void {
		self::assertSame(
			self::version_2_secret( 'test-salt' ),
			Honeypot::get_secret_name_for_post(),
			'The field name must stay byte-for-byte what Antispam Bee 2.x produced for the same salt'
		);
	}

	/**
	 * The names have to survive salt rotation and site migration, which is the
	 * whole reason the secret is stored instead of derived on every call.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_stored_secret_is_used_even_when_the_salt_changed(): void {
		when( 'get_option' )->justReturn( 'storedvalue' );
		when( 'wp_salt' )->justReturn( 'a-completely-different-salt' );

		self::assertSame(
			'storedvalue',
			Honeypot::get_secret_name_for_post(),
			'A stored secret must win over anything the current salt would derive'
		);
	}

	/**
	 * What lands in the options table has to be the finished field name, which is
	 * public in every comment form anyway — never the salt it came from, which
	 * would copy a `wp-config.php` secret into the database.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_stored_value_is_the_secret_and_not_the_salt(): void {
		$stored = null;
		when( 'get_option' )->justReturn( '' );
		when( 'add_option' )->alias(
			static function ( $option, $value ) use ( &$stored ) {
				$stored = $value;

				return true;
			}
		);

		$secret = Honeypot::get_secret_name_for_post();

		self::assertSame( $secret, $stored, 'The stored value should be the secret the form renders' );
		self::assertSame( self::version_2_secret( 'test-salt' ), $stored, 'The secret should be the derived one' );
		self::assertStringNotContainsString( 'test-salt', (string) $stored, 'The salt must never reach the database' );
	}

	/**
	 * Two requests can miss the option at the same time. Whichever one gets its
	 * `add_option()` in first defines the names, and the other has to adopt it
	 * rather than keep a value it never stored.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_concurrently_stored_secret_wins(): void {
		$calls = 0;
		when( 'get_option' )->alias(
			static function () use ( &$calls ) {
				++$calls;

				// Missing on the first read, present on the re-read after the failed write.
				return $calls > 1 ? 'winningvalue' : '';
			}
		);
		when( 'add_option' )->justReturn( false );

		self::assertSame(
			'winningvalue',
			Honeypot::get_secret_name_for_post(),
			'A secret stored by a concurrent request should be adopted'
		);
	}

	/**
	 * The secret is used as an HTML id, which may not start with a digit.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_filtered_secret_still_starts_with_a_letter(): void {
		when( 'get_option' )->justReturn( '7abcdefghi' );

		self::assertSame(
			'habcdefghi',
			Honeypot::get_secret_name_for_post(),
			'A stored or filtered secret starting with a digit should be corrected'
		);
	}

	/**
	 * Antispam Bee 2.x's derivation, spelled out here rather than called, so the
	 * tests assert against the old behaviour and not against the implementation.
	 *
	 * @param string $salt The salt to derive from.
	 *
	 * @return string The secret version 2 produced for that salt.
	 */
	private static function version_2_secret( string $salt ): string {
		$secret     = substr( sha1( md5( 'comment-id' . $salt ) ), 0, 10 );
		$first_char = substr( $secret, 0, 1 );

		return is_numeric( $first_char )
			? chr( (int) $first_char + 97 ) . substr( $secret, 1 )
			: $secret;
	}

	protected function set_up(): void {
		parent::set_up();
		when( 'esc_attr' )->returnArg();
		when( 'esc_js' )->returnArg();
		when( 'wp_salt' )->justReturn( 'test-salt' );
		when( 'get_option' )->justReturn( '' );
		when( 'add_option' )->justReturn( true );
	}
}
