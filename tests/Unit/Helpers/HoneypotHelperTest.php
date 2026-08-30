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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_supports_input_field(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<input type="text" id="comment" name="comment" class="my-class">';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		// The visible input must keep its id and class, only the name becomes
		// the secret, and a hidden honeypot input with the comment name follows.
		self::assertStringContainsString(
			'<input type="text" id="comment" name="' . $name . '" class="my-class">',
			$result,
			'The visible input should keep its id and class and get the secret name'
		);
		self::assertStringContainsString( 'aria-hidden="true"', $result, 'The honeypot input should be hidden' );
		self::assertStringContainsString( 'name="comment"', $result, 'The honeypot bait name should survive' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_supports_unquoted_input_field(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<input type="text" id=comment name=comment class="my-class">';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertStringContainsString(
			'name="' . $name . '" class="my-class"><input name="comment"',
			$result,
			'Unquoted attributes should keep the class intact and append a hidden honeypot with the comment name'
		);
		self::assertStringContainsString( 'aria-hidden="true"', $result, 'The honeypot input should be hidden' );
		self::assertStringContainsString( 'name="comment"', $result, 'The honeypot bait name should survive' );
	}

	/**
	 * A prefixed input must not be mistaken for the comment field when a
	 * distinct comment input follows it.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_ignores_prefixed_input_field(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<input type="text" id="comment-extra" name="comment-extra"><input type="text" id="comment" name="comment">';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertStringContainsString( 'id="comment-extra" name="comment-extra">', $result, 'The prefixed input should be left untouched' );
		self::assertStringContainsString( 'id="comment"', $result, 'The comment input should keep its id' );
		self::assertStringContainsString( 'name="' . $name . '"', $result, 'The comment input should get the secret name' );
		self::assertStringContainsString( 'aria-hidden="true"', $result, 'A honeypot should be appended after the comment input' );
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
		// Version 2's formula, spelled out here rather than called, so this asserts
		// against the old behaviour instead of against the current implementation.
		$secret     = substr( sha1( md5( 'comment-id' . 'test-salt' ) ), 0, 10 );
		$first_char = substr( $secret, 0, 1 );
		$expected   = is_numeric( $first_char )
			? chr( (int) $first_char + 97 ) . substr( $secret, 1 )
			: $secret;

		self::assertSame(
			$expected,
			Honeypot::get_secret_name_for_post(),
			'The field name must stay byte-for-byte what Antispam Bee 2.x produced for the same salt'
		);
	}

	protected function set_up(): void {
		parent::set_up();
		when( 'esc_attr' )->returnArg();
		when( 'esc_js' )->returnArg();
		when( 'wp_salt' )->justReturn( 'test-salt' );
	}
}
