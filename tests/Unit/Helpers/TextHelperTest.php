<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\TextHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see TextHelper}.
 */
class TextHelperTest extends TestCase {

	/**
	 * A text that is not valid UTF-8.
	 *
	 * @var string
	 */
	private const INVALID_UTF8 = "abc\xC3\x28 def";

	public function test_normalize_whitespace_collapses_and_trims(): void {
		self::assertSame(
			'A small text',
			TextHelper::normalize_whitespace( "  A \n\r\t small    text  " ),
			'All whitespace should be collapsed into single spaces and the text should be trimmed'
		);

		self::assertSame(
			'a b',
			TextHelper::normalize_whitespace( "a\xC2\xA0b" ),
			'A non-breaking space should be collapsed as well'
		);

		self::assertSame(
			'',
			TextHelper::normalize_whitespace( '   ' ),
			'A text consisting only of whitespace should become an empty string'
		);
	}

	public function test_normalize_whitespace_handles_invalid_utf8(): void {
		self::assertSame(
			"abc\xC3\x28 def",
			TextHelper::normalize_whitespace( self::INVALID_UTF8 ),
			'An invalid UTF-8 text should be normalized bytewise instead of raising an error'
		);
	}

	public function test_count_words(): void {
		self::assertSame(
			9,
			TextHelper::count_words( 'A small text passes the test. Lets check this.' ),
			'Words should be counted by their whitespace delimiters'
		);

		self::assertSame(
			2,
			TextHelper::count_words( " Two \n words  " ),
			'Surrounding and repeated whitespace should not be counted as words'
		);

		self::assertSame( 0, TextHelper::count_words( '' ), 'An empty text should have no words' );

		self::assertSame(
			1,
			TextHelper::count_words( '这是一个中文垃圾评论' ),
			'A text without word delimiters should be counted as a single word'
		);
	}

	public function test_count_characters(): void {
		self::assertSame(
			10,
			TextHelper::count_characters( '这是一个中文垃圾评论' ),
			'Multibyte characters should be counted as single characters'
		);

		self::assertSame( 0, TextHelper::count_characters( '' ), 'An empty text should have no characters' );

		self::assertSame(
			0,
			TextHelper::count_characters( self::INVALID_UTF8 ),
			'An invalid UTF-8 text should have no countable characters'
		);
	}

	public function test_count_spaceless_script_letters(): void {
		self::assertSame(
			10,
			TextHelper::count_spaceless_script_letters( '这是一个中文垃圾评论' ),
			'Han characters should be counted'
		);

		self::assertSame(
			8,
			TextHelper::count_spaceless_script_letters( 'これはスパムです' ),
			'Hiragana and Katakana characters should be counted'
		);

		self::assertSame(
			13,
			TextHelper::count_spaceless_script_letters( '안녕하세요 이것은 스팸입니다' ),
			'Hangul characters should be counted, spaces should not'
		);

		self::assertSame(
			23,
			TextHelper::count_spaceless_script_letters( 'สวัสดีครับ นี่คือข้อความ' ),
			'Thai characters should be counted, spaces should not'
		);

		self::assertSame(
			0,
			TextHelper::count_spaceless_script_letters( 'Ein völlig normaler Kommentar.' ), // spellchecker:disable-line
			'A latin text should have no letters of a script without word delimiters'
		);

		self::assertSame(
			4,
			TextHelper::count_spaceless_script_letters( '中文，垃圾。' ),
			'Ideographic punctuation should not be counted, even though it belongs to the Han script'
		);

		self::assertSame(
			0,
			TextHelper::count_spaceless_script_letters( '这是' . self::INVALID_UTF8 ),
			'An invalid UTF-8 text should have no countable characters'
		);
	}

	public function test_count_letters(): void {
		self::assertSame(
			26,
			TextHelper::count_letters( 'Ein völlig normaler Kommentar.' ), // spellchecker:disable-line
			'Umlauts should be counted, spaces and punctuation should not'
		);

		self::assertSame(
			4,
			TextHelper::count_letters( '中文，垃圾。' ),
			'Fullwidth punctuation should not be counted as letters'
		);

		self::assertSame(
			0,
			TextHelper::count_letters( '1234 !?' ),
			'Digits, spaces and punctuation should not be counted as letters'
		);
	}
}
