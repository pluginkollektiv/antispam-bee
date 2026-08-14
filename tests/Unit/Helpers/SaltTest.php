<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\Salt;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;

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

	public function test_generate_asks_for_a_value_the_check_accepts(): void {
		expect( 'wp_generate_password' )
			->once()
			->with( 64, true, true )
			->andReturn( 'x9!Kq2#Vz7$Lp4%Rn8^Mb6&Tw3*Yh5(Jg1)Fd0-Sa7+Ce2=Vu9~Io4Pj6Zq8Xm3B' );

		self::assertTrue(
			Salt::is_generated( Salt::generate() ),
			'A generated salt must satisfy the check the plugin applies to configured ones'
		);
	}
}
