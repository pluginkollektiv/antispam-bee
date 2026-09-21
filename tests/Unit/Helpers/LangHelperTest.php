<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\LangHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see LangHelper}.
 */
class LangHelperTest extends TestCase {

	/**
	 * Individual codes the detection service reports for a macrolanguage that
	 * carries an ISO 639-1 code of its own.
	 *
	 * Taken from the code set of the pinned franc version (see
	 * `tests/e2e/language-api/package.json`). An unmapped entry here never
	 * matches the allowed-languages setting, so every comment in that language
	 * is flagged as spam.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function macrolanguage_members(): array {
		// spellchecker:off.
		return [
			'Tosk Albanian'                => [ 'als', 'sq' ],
			'Standard Arabic'              => [ 'arb', 'ar' ],
			'Central Aymara'               => [ 'ayr', 'ay' ],
			'North Azerbaijani'            => [ 'azj', 'az' ],
			'Central Kurdish'              => [ 'ckb', 'ku' ],
			'Standard Estonian'            => [ 'ekk', 'et' ],
			'Pular'                        => [ 'fuf', 'ff' ],
			'Nigerian Fulfulde'            => [ 'fuv', 'ff' ],
			'Halh Mongolian'               => [ 'khk', 'mn' ],
			'Central Kanuri'               => [ 'knc', 'kr' ],
			'Standard Latvian'             => [ 'lvs', 'lv' ],
			'Nepali'                       => [ 'npi', 'ne' ],
			'Iranian Persian'              => [ 'pes', 'fa' ],
			'Plateau Malagasy'             => [ 'plt', 'mg' ],
			'Dari'                         => [ 'prs', 'fa' ],
			'Chimborazo Highland Quichua'  => [ 'qug', 'qu' ],
			'Ayacucho Quechua'             => [ 'quy', 'qu' ],
			'Cusco Quechua'                => [ 'quz', 'qu' ],
			'Swahili'                      => [ 'swh', 'sw' ],
			'Northern Uzbek'               => [ 'uzn', 'uz' ],
			'Malay'                        => [ 'zlm', 'ms' ],
			'Mandarin Chinese'             => [ 'cmn', 'zh' ],
			'Cantonese'                    => [ 'yue', 'zh' ],
		];
		// spellchecker:on.
	}

	/**
	 * @dataProvider macrolanguage_members
	 */
	public function test_macrolanguage_member_maps_to_the_macrolanguage_code( string $franc_code, string $expected ): void {
		self::assertSame(
			$expected,
			LangHelper::map( $franc_code ),
			sprintf(
				'»%s« must map to »%s«, otherwise the language can never match the allowed-languages setting',
				$franc_code,
				$expected
			)
		);
	}

	public function test_three_letter_code_with_an_iso_639_1_equivalent_is_mapped(): void {
		self::assertSame( 'de', LangHelper::map( 'deu' ), 'German should map to its ISO 639-1 code' );
		self::assertSame( 'en', LangHelper::map( 'eng' ), 'English should map to its ISO 639-1 code' );
	}

	public function test_code_without_an_iso_639_1_equivalent_is_returned_unchanged(): void {
		// Cebuano and Bhojpuri have no ISO 639-1 code, so flagging them is intended.
		self::assertSame( 'ceb', LangHelper::map( 'ceb' ), 'a code with no ISO 639-1 equivalent should be left alone' );
		self::assertSame( 'bho', LangHelper::map( 'bho' ), 'a code with no ISO 639-1 equivalent should be left alone' );
	}

	public function test_every_mapped_code_targets_a_two_letter_code(): void {
		$reflection = new \ReflectionMethod( LangHelper::class, 'map' );
		$source     = file( $reflection->getFileName() );
		$body       = implode( '', array_slice( $source, $reflection->getStartLine(), $reflection->getEndLine() ) );

		preg_match_all( "/'([a-z]{3})'\s*=>\s*'([a-z]+)'/", $body, $matches, PREG_SET_ORDER );

		self::assertNotEmpty( $matches, 'the code table should not be empty' );

		foreach ( $matches as $match ) {
			self::assertSame(
				2,
				strlen( $match[2] ),
				sprintf( '»%s« must map to a two-letter ISO 639-1 code, got »%s«', $match[1], $match[2] )
			);
		}
	}
}
