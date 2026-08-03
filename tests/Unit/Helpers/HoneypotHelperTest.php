<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'NONCE_SALT' ) ) {
	define( 'NONCE_SALT', 'test-nonce-salt' );
}

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

	protected function set_up(): void {
		parent::set_up();
		when( 'esc_attr' )->returnArg();
		when( 'esc_js' )->returnArg();
	}
}
