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
		when( 'delete_option' )->justReturn( true );
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

	/**
	 * Seeing the honeypot in a rendered form answers what an unguarded submission
	 * raised, so its record goes; a failed injection keeps it.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_successful_injection_clears_the_unguarded_submission(): void {
		$deleted = [];
		when( 'get_option' )->justReturn( false );
		when( 'update_option' )->justReturn( true );
		when( 'delete_option' )->alias(
			static function ( $name ) use ( &$deleted ) {
				$deleted[] = $name;

				return true;
			}
		);

		Honeypot::record_injection( true );
		Honeypot::record_injection( false );

		self::assertSame( [ Honeypot::UNGUARDED_SUBMISSION_OPTION ], $deleted );
	}

	/**
	 * Every bot posting without the form lands here, so only the first one writes.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_an_unguarded_submission_is_recorded_once(): void {
		$added = [];
		when( 'add_option' )->alias(
			static function ( $name, $value ) use ( &$added ) {
				$added[] = [ $name, is_int( $value ) ];

				return true;
			}
		);

		when( 'get_option' )->justReturn( false );
		Honeypot::record_unguarded_submission();

		when( 'get_option' )->justReturn( 1727000000 );
		Honeypot::record_unguarded_submission();

		self::assertSame( [ [ Honeypot::UNGUARDED_SUBMISSION_OPTION, true ] ], $added );
	}

	/**
	 * @dataProvider provide_protection_states
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @param mixed $state      The stored injection state.
	 * @param mixed $unguarded  The stored unguarded submission.
	 * @param bool  $missing    Whether the protection counts as missing.
	 */
	public function test_protection_missing( $state, $unguarded, bool $missing ): void {
		when( 'get_option' )->alias(
			static function ( $name ) use ( $state, $unguarded ) {
				return 'antispam_bee_honeypot_injection' === $name ? $state : $unguarded;
			}
		);

		self::assertSame( $missing, Honeypot::protection_missing() );
	}

	/**
	 * Injection states, unguarded submissions, and whether the honeypot is missing.
	 *
	 * Plain values rather than the class constants: data providers run in the main
	 * process, and loading the helper there breaks the tests that overload it.
	 *
	 * @return array<string, array{0: mixed, 1: mixed, 2: bool}>
	 */
	public function provide_protection_states(): array {
		return [
			'injected'                             => [ 'injected', false, false ],
			'injected, older unguarded submission' => [ 'injected', 1727000000, false ],
			'failed'                               => [ 'failed', false, true ],
			'never rendered, comments submitted'   => [ false, 1727000000, true ],
			'never rendered, nothing submitted'    => [ false, false, false ],
		];
	}
}
