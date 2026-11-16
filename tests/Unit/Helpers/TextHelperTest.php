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

	/**
	 * `\s` under `/u` covers `\p{Z}`, but a text can be spaced out with code
	 * points that render as a gap and belong to another category. A text
	 * separated by those counted as one word, which is how an arbitrarily long
	 * comment could slip under the minimum-words threshold and never be checked.
	 */
	public function test_count_words_treats_blank_rendering_characters_as_separators(): void {
		$words = [ 'the', 'quick', 'brown', 'fox', 'jumps', 'over', 'the', 'lazy', 'dog', 'and', 'keeps', 'going' ];

		$separators = [
			'space'                 => ' ',
			'no-break space'        => "\u{00A0}",
			'ideographic space'     => "\u{3000}",
			'braille pattern blank' => "\u{2800}",
			'hangul filler'         => "\u{3164}",
			'zero width space'      => "\u{200B}",
			'zero width no-break'   => "\u{FEFF}",
		];

		foreach ( $separators as $description => $separator ) {
			self::assertSame(
				count( $words ),
				TextHelper::count_words( implode( $separator, $words ) ),
				"a text spaced out with a $description should still be counted word by word"
			);
		}
	}

	public function test_normalize_whitespace_collapses_blank_rendering_characters(): void {
		self::assertSame(
			'one two',
			TextHelper::normalize_whitespace( "one\u{2800}\u{200B}two" ),
			'blank-rendering characters should collapse like any other whitespace'
		);
	}

	/**
	 * The three full stops a browser treats as a label separator, see #931.
	 */
	public function test_fold_fullwidth_ascii_folds_the_dots_a_browser_resolves(): void {
		$dots = [
			'ideographic full stop'           => "\u{3002}",
			'fullwidth full stop'             => "\u{FF0E}",
			'halfwidth ideographic full stop' => "\u{FF61}",
		];

		foreach ( $dots as $description => $dot ) {
			self::assertSame(
				'http://bit.ly/foo',
				TextHelper::fold_fullwidth_ascii( "http://bit{$dot}ly/foo" ),
				"a host written with a $description should fold to the ASCII host"
			);
		}
	}

	public function test_fold_fullwidth_ascii_folds_the_whole_fullwidth_block(): void {
		self::assertSame(
			'bit.ly/Foo-9?x=1',
			TextHelper::fold_fullwidth_ascii( "\u{FF42}\u{FF49}\u{FF54}\u{FF0E}\u{FF4C}\u{FF59}\u{FF0F}\u{FF26}\u{FF4F}\u{FF4F}\u{FF0D}\u{FF19}\u{FF1F}\u{FF58}\u{FF1D}\u{FF11}" ),
			'fullwidth letters, digits and punctuation should fold to their ASCII counterparts'
		);

		self::assertSame(
			'!',
			TextHelper::fold_fullwidth_ascii( "\u{FF01}" ),
			'the first character of the fullwidth block should fold to the first printable ASCII character'
		);
		self::assertSame(
			'~',
			TextHelper::fold_fullwidth_ascii( "\u{FF5E}" ),
			'the last character of the fullwidth block should fold to the last printable ASCII character'
		);
	}

	public function test_fold_fullwidth_ascii_leaves_other_text_alone(): void {
		$untouched = [
			'plain ASCII'                 => 'http://bit.ly/foo and some text.',
			'umlauts'                     => 'Grüße aus Köln',
			'CJK letters'                 => 'こんにちは',
			'fullwidth space (U+3000)'    => "a\u{3000}b",
			'middle dot (U+00B7)'         => "bit\u{00B7}ly",
			'one-dot leader (U+2024)'     => "bit\u{2024}ly",
			'character before the block'  => "\u{FF00}",
			'character after the block'   => "\u{FF5F}",
		];

		foreach ( $untouched as $description => $text ) {
			self::assertSame(
				$text,
				TextHelper::fold_fullwidth_ascii( $text ),
				"a text with $description should not be changed"
			);
		}
	}

	public function test_fold_fullwidth_ascii_handles_invalid_utf8(): void {
		self::assertSame(
			self::INVALID_UTF8,
			TextHelper::fold_fullwidth_ascii( self::INVALID_UTF8 ),
			'a text that is not valid UTF-8 should be returned unchanged instead of becoming empty'
		);
	}
}
