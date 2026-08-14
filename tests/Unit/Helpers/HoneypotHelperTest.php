<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'NONCE_SALT' ) ) {
	// Shaped like a real salt: 64 characters of printable ASCII, no whitespace.
	define( 'NONCE_SALT', 'x9!Kq2#Vz7$Lp4%Rn8^Mb6&Tw3*Yh5(Jg1)Fd0-Sa7+Ce2=Vu9~Io4Pj6Zq8Xm3B' );
}

/**
 * Unit tests for {@see Honeypot} (helper).
 */
class HoneypotHelperTest extends TestCase {

	/**
	 * The salt the stored option holds.
	 *
	 * @var string
	 */
	private $stored_salt = 'stored-test-salt';

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_field_names_are_derived_from_the_stored_salt(): void {
		$this->stored_salt = 'a-stored-salt';

		self::assertSame(
			Honeypot::ensure_secret_starts_with_letter(
				substr( sha1( md5( 'comment-id' . 'a-stored-salt' ) ), 0, 10 )
			),
			Honeypot::get_secret_name_for_post(),
			'The field name should be derived from the stored salt'
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_field_names_are_stable_while_the_stored_salt_is(): void {
		self::assertSame(
			Honeypot::get_secret_name_for_post(),
			Honeypot::get_secret_name_for_post(),
			'The same stored salt must always produce the same field name'
		);
	}

	/**
	 * Storing the salt must not invalidate comment forms that are already sitting
	 * in a page cache, so the stored value is seeded with the one the previous
	 * derivation produced.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_salt_is_seeded_from_nonce_salt(): void {
		$this->stored_salt = '';
		$legacy            = substr( sha1( NONCE_SALT ), 0, 10 );

		expect( 'add_option' )
			->once()
			->with( Honeypot::SALT_OPTION, $legacy )
			->andReturn( true );

		self::assertSame(
			Honeypot::ensure_secret_starts_with_letter(
				substr( sha1( md5( 'comment-id' . $legacy ) ), 0, 10 )
			),
			Honeypot::get_secret_name_for_post(),
			'The field name should be unchanged from what the previous derivation produced'
		);
	}


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

	protected function set_up(): void {
		parent::set_up();
		when( 'esc_attr' )->returnArg();
		when( 'esc_js' )->returnArg();
		when( 'wp_salt' )->justReturn( 'test-salt' );
		when( 'get_option' )->alias(
			function () {
				return $this->stored_salt;
			}
		);
	}
}
