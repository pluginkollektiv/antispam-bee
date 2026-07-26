<?php
/**
 * Text helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Helper to analyze texts and the scripts they are written in.
 */
class TextHelper {

	/**
	 * Letters, including combining marks.
	 *
	 * @var string
	 */
	private const LETTERS = '[\p{L}\p{M}]';

	/**
	 * Letters of scripts that do not use spaces to delimit words.
	 *
	 * The lookahead keeps the punctuation of those scripts out of the match. Some of it,
	 * for example the ideographic full stop, belongs to the Han script as well, which
	 * would make the result incomparable to the number of letters of the whole text.
	 *
	 * @var string
	 */
	private const SPACELESS_SCRIPT_LETTERS = '/(?=' . self::LETTERS . ')[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Thai}\p{Lao}\p{Khmer}\p{Myanmar}\p{Tibetan}]/u';

	/**
	 * Collapse all whitespace into single spaces and trim the text.
	 *
	 * @param string $text The text.
	 *
	 * @return string The normalized text.
	 */
	public static function normalize_whitespace( string $text ): string {
		$normalized = preg_replace( '/\s+/u', ' ', $text );

		if ( null === $normalized ) {
			// The text is not valid UTF-8, so fall back to matching bytes.
			$normalized = preg_replace( '/\s+/', ' ', $text );
		}

		return trim( (string) $normalized );
	}

	/**
	 * Count the words of a text, delimited by whitespace.
	 *
	 * @param string $text The text.
	 *
	 * @return int The number of words.
	 */
	public static function count_words( string $text ): int {
		$words = preg_split( '/ /', self::normalize_whitespace( $text ), -1, PREG_SPLIT_NO_EMPTY );

		return is_array( $words ) ? count( $words ) : 0;
	}

	/**
	 * Count the characters of a text.
	 *
	 * @param string $text The text.
	 *
	 * @return int The number of characters.
	 */
	public static function count_characters( string $text ): int {
		return self::count_matches( '/./u', $text );
	}

	/**
	 * Count the letters of scripts that do not use spaces to delimit words.
	 *
	 * Those are, for example, the Chinese, Japanese, Korean or Thai scripts. The result
	 * can be compared to {@see TextHelper::count_letters()} to tell how much of a text
	 * is written in such a script.
	 *
	 * @param string $text The text.
	 *
	 * @return int The number of letters written in such a script.
	 */
	public static function count_spaceless_script_letters( string $text ): int {
		return self::count_matches( self::SPACELESS_SCRIPT_LETTERS, $text );
	}

	/**
	 * Count the letters of a text, including combining marks.
	 *
	 * Digits, punctuation and symbols are not counted.
	 *
	 * @param string $text The text.
	 *
	 * @return int The number of letters.
	 */
	public static function count_letters( string $text ): int {
		return self::count_matches( '/' . self::LETTERS . '/u', $text );
	}

	/**
	 * Count the matches of a pattern in a text.
	 *
	 * A text that cannot be matched, because it is not valid UTF-8, has no matches.
	 *
	 * @param string $pattern The pattern to match.
	 * @param string $text    The text.
	 *
	 * @return int The number of matches.
	 */
	private static function count_matches( string $pattern, string $text ): int {
		$count = preg_match_all( $pattern, $text );

		return is_int( $count ) ? $count : 0;
	}
}
