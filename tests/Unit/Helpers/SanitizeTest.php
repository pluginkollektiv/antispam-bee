<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Sanitize;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Sanitize}.
 */
class SanitizeTest extends TestCase {

	public function test_iso_codes_keeps_two_letter_codes(): void {
		self::assertSame(
			[ 'DE', 'fr' ],
			Sanitize::iso_codes( [ 'DE', 'fr' ] ),
			'Two-letter codes should be kept as they are, without changing their case'
		);
	}

	public function test_iso_codes_trims_surrounding_whitespace(): void {
		self::assertSame(
			[ 'DE', 'FR' ],
			Sanitize::iso_codes( [ "  DE\t", "\nFR " ] ),
			'Surrounding whitespace should be trimmed rather than making the code invalid'
		);
	}

	public function test_iso_codes_drops_anything_that_is_not_a_country_code(): void {
		self::assertSame(
			[],
			Sanitize::iso_codes(
				[
					'',
					'D',
					'DEU',
					'D3',
					'12',
					'-)',
				]
			),
			'Only two-character alphabetic codes should survive'
		);
	}

	public function test_iso_codes_preserves_keys_of_the_codes_it_keeps(): void {
		self::assertSame(
			[
				0 => 'DE',
				2 => 'FR',
			],
			Sanitize::iso_codes( [ 'DE', 'nope', 'FR' ] ),
			'Filtering should not reindex the list'
		);
	}

	public function test_iso_codes_accepts_an_empty_list(): void {
		self::assertSame(
			[],
			Sanitize::iso_codes( [] ),
			'An empty list has nothing to sanitize'
		);
	}

	/**
	 * Registered as the settings API `sanitize_callback`, so WordPress hands this
	 * whatever the request posted. A scalar posted for the option used to reach a
	 * declared `array` parameter and raise a `TypeError`, turning a malformed save
	 * into a fatal instead of a rejected value.
	 */
	public function test_sanitize_options_discards_a_value_that_is_not_an_array(): void {
		$stored = [ 'general' => [ 'some_option' => 'on' ] ];
		when( 'get_option' )->justReturn( $stored );
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0' ] );

		foreach ( [ 'a string' => 'not-an-array', 'null' => null, 'a number' => 42 ] as $description => $value ) {
			self::assertIsArray(
				Sanitize::sanitize_options( $value ),
				"Posting $description should be discarded rather than raise a TypeError"
			);
		}
	}
}
