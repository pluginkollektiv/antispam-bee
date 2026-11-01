<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;
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
	 * A data-name attribute must not be mistaken for the comment field's name,
	 * so the rewrite has to hit the real name attribute only.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_rewrites_the_real_name_when_a_data_name_attribute_precedes_it(): void {
		$name   = Honeypot::get_secret_name_for_post();
		$markup = '<input type="text" data-name="honeypot-decoy" id="comment" name="comment">';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertStringContainsString(
			'data-name="honeypot-decoy"',
			$result,
			'The data-name attribute should keep its value'
		);
		self::assertStringContainsString(
			'name="' . $name . '"',
			$result,
			'The real name attribute should become the secret'
		);
		self::assertStringNotContainsString(
			'name="' . $name . '-decoy"',
			$result,
			'The rewrite must not corrupt the data-name attribute'
		);
	}

	/**
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
	 * An id and name carrying regex metacharacters must still match the literal
	 * attribute value instead of being interpreted as a pattern.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_handles_regex_metacharacters_in_the_field_id_and_name(): void {
		$id     = 'comment.body';
		$name   = 'comment[body]';
		$markup = '<input type="text" id="' . $id . '" name="' . $name . '">';

		$result = Honeypot::inject( $markup, [ 'field_id' => $id ] );

		self::assertStringContainsString(
			'name="' . Honeypot::get_secret_name_for_post() . '"',
			$result,
			'A field id and name with regex metacharacters should still be rewritten'
		);
		self::assertStringContainsString( 'aria-hidden="true"', $result, 'A honeypot should be appended' );
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

	/**
	 * A form the injection placed the honeypot into is recorded, which is what
	 * arms the invalid-request verdict for submissions without the secret field.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_record_injection_stores_a_success(): void {
		when( 'get_option' )->justReturn( false );
		expect( 'update_option' )->once()->with( Honeypot::INJECTION_STATE_OPTION, Honeypot::INJECTION_STATE_INJECTED );

		Honeypot::record_injection( true );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_record_injection_stores_a_failure(): void {
		when( 'get_option' )->justReturn( Honeypot::INJECTION_STATE_INJECTED );
		expect( 'update_option' )->once()->with( Honeypot::INJECTION_STATE_OPTION, Honeypot::INJECTION_STATE_FAILED );

		Honeypot::record_injection( false );
	}

	/**
	 * The outcome is recorded on every comment form render, so an unchanged one
	 * must not cost a database write.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_record_injection_does_not_write_an_unchanged_outcome(): void {
		expect( 'update_option' )->never();

		when( 'get_option' )->justReturn( Honeypot::INJECTION_STATE_INJECTED );
		Honeypot::record_injection( true );

		when( 'get_option' )->justReturn( Honeypot::INJECTION_STATE_FAILED );
		Honeypot::record_injection( false );
	}

	/**
	 * Only a recorded success counts: a failure and a site that never rendered a
	 * form through the plugin must both leave the verdict disarmed.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_injection_observed_only_after_a_recorded_success(): void {
		when( 'get_option' )->justReturn( false );
		self::assertFalse( Honeypot::injection_observed(), 'no form rendered yet' );

		when( 'get_option' )->justReturn( Honeypot::INJECTION_STATE_FAILED );
		self::assertFalse( Honeypot::injection_observed(), 'injection failed' );

		when( 'get_option' )->justReturn( Honeypot::INJECTION_STATE_INJECTED );
		self::assertTrue( Honeypot::injection_observed(), 'injection succeeded' );
	}

	/**
	 * The pattern that finds the comment textarea uses the `/x` modifier, so an
	 * unescaped `#` in the field name comments out the rest of that line and the
	 * match silently stops working — leaving the form without a honeypot.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_handles_a_field_name_with_regex_metacharacters(): void {
		when( 'esc_attr' )->returnArg();
		when( 'esc_js' )->returnArg();

		$markup = '<textarea id="comment" name="comment[body]#x"></textarea>';

		$result = Honeypot::inject( $markup, [ 'field_id' => 'comment' ] );

		self::assertNotSame(
			$markup,
			$result,
			'a name containing regex metacharacters must still be matched and rewritten'
		);
		self::assertStringContainsString(
			'name="' . Honeypot::get_secret_name_for_post() . '"',
			$result,
			'the injection should have completed and renamed the field'
		);
	}

	/**
	 * Anything on `comment_form_field_comment` can hand this an empty string — a
	 * theme rendering the comment textarea itself, for one. `loadHTML()` throws a
	 * ValueError on that, which would fatal every page with a comment form.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_returns_empty_markup_untouched(): void {
		self::assertSame( '', Honeypot::inject( '', [ 'field_id' => 'comment' ] ) );
		self::assertSame( '   ', Honeypot::inject( '   ', [ 'field_id' => 'comment' ] ) );
	}

	/**
	 * A filter callback that forgets to return hands this null. Without a guard
	 * the non-nullable parameter throws a TypeError before the body even runs.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_survives_a_null_from_the_filter(): void {
		self::assertSame( '', Honeypot::inject( null, [ 'field_id' => 'comment' ] ) );
	}

	/**
	 * A form not built with `comment_form()` often has no `id="comment"`. With a
	 * field name to go by, its textarea still gets the honeypot.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_finds_a_comment_textarea_without_the_id_by_its_name(): void {
		$markup = '<form action="/wp-comments-post.php" method="post"><textarea name="comment" class="field"></textarea></form>';

		$injected = Honeypot::inject(
			$markup,
			[
				'field_id'   => 'comment',
				'field_name' => 'comment',
			]
		);

		self::assertStringContainsString( 'name="' . Honeypot::get_secret_name_for_post() . '"', $injected, 'the visible field was not renamed' );
		self::assertStringContainsString( '<textarea name="comment" aria-hidden="true"', $injected, 'the decoy was not added' );
		self::assertStringNotContainsString( 'id=""', $injected, 'a field without an id must not get an empty one' );
		self::assertStringNotContainsString( '<script', $injected, 'there is no id to swap back' );
	}

	/**
	 * Without a field name the lookup stays on the id, as for the form field filter.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_without_a_field_name_needs_the_id(): void {
		$markup = '<textarea name="comment"></textarea>';

		self::assertSame( $markup, Honeypot::inject( $markup, [ 'field_id' => 'comment' ] ) );
	}
}
