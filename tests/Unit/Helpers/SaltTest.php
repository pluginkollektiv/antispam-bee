<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Salt;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Salt}.
 */
class SaltTest extends TestCase {

	public function test_a_generated_salt_is_recognised(): void {
		$salts = [
			'from the WordPress secret-key service' => 'x9!Kq2#Vz7$Lp4%Rn8^Mb6&Tw3*Yh5(Jg1)Fd0-Sa7+Ce2=Vu9~Io4Pj6Zq8Xm3B',
			'alphanumeric only'                     => 'B3mX8qZ6jP4oI9uV2eC7aS0dF1gJ5hY3wT6bM8nR4pL7zV2qK9',
			'exactly at the length limit'           => 'B3mX8qZ6jP4oI9uV2eC7aS0dF1gJ5hY3',
		];

		foreach ( $salts as $description => $salt ) {
			self::assertTrue(
				Salt::is_generated( $salt ),
				"A salt $description should be recognised as generated"
			);
		}
	}

	/**
	 * The placeholder is translated in localised WordPress packages, so it cannot
	 * be recognised by comparing against the English phrase. Every translation is
	 * a natural-language phrase, and those contain whitespace where a generated
	 * salt never does.
	 */
	public function test_a_placeholder_phrase_is_rejected_in_any_language(): void {
		$placeholders = [
			'the English placeholder' => 'put your unique phrase here',
			'a German translation'    => 'füge hier deine einmalig genutzte Zeichenfolge ein', // spellchecker:disable-line
			'a long phrase'           => 'this phrase is comfortably longer than the length limit',
		];

		foreach ( $placeholders as $description => $placeholder ) {
			self::assertFalse(
				Salt::is_generated( $placeholder ),
				"$description should not be treated as a secret"
			);
		}
	}

	public function test_short_and_empty_values_are_rejected(): void {
		$values = [
			'an empty string'      => '',
			'a short random value' => 'B3mX8qZ6jP4oI9uV',
			'one character short'  => 'B3mX8qZ6jP4oI9uV2eC7aS0dF1gJ5hY',
		];

		foreach ( $values as $description => $value ) {
			self::assertFalse(
				Salt::is_generated( $value ),
				"$description should not be treated as a secret"
			);
		}
	}

	/**
	 * The point of preferring the constant: a site that configured its salts keeps
	 * the identifiers it already had in Antispam Bee 2.x, so honeypot field names
	 * sitting in a page cache stay valid across the upgrade.
	 */
	public function test_a_configured_salt_is_preferred_over_the_managed_one(): void {
		when( 'wp_salt' )->justReturn( 'managed-salt' );
		$configured = 'B3mX8qZ6jP4oI9uV2eC7aS0dF1gJ5hY3wT6bM8nR4pL7zV2qK9';

		self::assertSame(
			$configured,
			Salt::resolve( $configured ),
			'A configured salt that looks generated should be used as it is'
		);
	}

	/**
	 * A placeholder must not become the secret the honeypot names derive from, or
	 * the field names would be identical — and so predictable — on every site that
	 * never configured its salts.
	 */
	public function test_a_salt_that_does_not_look_generated_falls_back(): void {
		when( 'wp_salt' )->justReturn( 'managed-salt' );

		$rejected = [
			'a placeholder phrase' => 'put your unique phrase here',
			'a short value'        => 'too-short',
			'an empty string'      => '',
		];

		foreach ( $rejected as $description => $value ) {
			self::assertSame(
				'managed-salt',
				Salt::resolve( $value ),
				"With $description the managed salt should be used instead"
			);
		}
	}

	/**
	 * `get()` passes null for a constant that is missing, and for one that
	 * `wp-config.php` defined as something other than a string.
	 */
	public function test_a_missing_salt_falls_back(): void {
		when( 'wp_salt' )->justReturn( 'managed-salt' );

		self::assertSame(
			'managed-salt',
			Salt::resolve( null ),
			'Without a configured salt the managed one should be used'
		);
	}

	/**
	 * `DebugMode` skips logging when no secret is available, so the empty string
	 * has to survive as an empty string rather than becoming a usable value.
	 */
	public function test_an_empty_managed_salt_is_passed_through(): void {
		when( 'wp_salt' )->justReturn( '' );

		self::assertSame( '', Salt::resolve( null ), 'An unavailable secret should stay empty' );
	}
}
