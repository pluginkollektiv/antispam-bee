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
	 * Characters that render as a blank without being whitespace.
	 *
	 * `\s` under `/u` covers `\p{Z}`, but a text can be spaced out with code
	 * points that show as a gap and belong to another category: the format
	 * characters `\p{Cf}` such as U+200B ZERO WIDTH SPACE and U+FEFF, U+2800
	 * BRAILLE PATTERN BLANK, and U+3164 HANGUL FILLER. A text separated by those
	 * counts as a single word, which is how a long comment can slip under a
	 * minimum-words threshold.
	 *
	 * @var string
	 */
	private const BLANK_RENDERING = '\p{Cf}\x{2800}\x{3164}';

	/**
	 * Collapse all whitespace into single spaces and trim the text.
	 *
	 * Characters that merely render as a blank are treated as whitespace too, so
	 * that counting words over the result cannot be defeated by spacing a text
	 * out with them.
	 *
	 * @param string $text The text.
	 *
	 * @return string The normalized text.
	 */
	public static function normalize_whitespace( string $text ): string {
		$normalized = preg_replace( '/[\s' . self::BLANK_RENDERING . ']+/u', ' ', $text );

		if ( null === $normalized ) {
			// The text is not valid UTF-8, so fall back to matching bytes.
			$normalized = preg_replace( '/\s+/', ' ', $text );
		}

		return trim( (string) $normalized );
	}

	/**
	 * Fold the characters a browser maps to ASCII before it resolves a host name.
	 *
	 * `bit。ly` opens bit.ly: the IDNA mapping (UTS #46) turns U+3002 IDEOGRAPHIC
	 * FULL STOP, U+FF0E FULLWIDTH FULL STOP and U+FF61 HALFWIDTH IDEOGRAPHIC FULL
	 * STOP into a label separator, and the fullwidth forms U+FF01–U+FF5E into the
	 * ASCII characters they are wider copies of. A pattern written for `bit\.ly`
	 * matches none of those spellings unless the subject is folded the same way
	 * first.
	 *
	 * Deliberately not `idn_to_ascii()`, which needs the `intl` extension that
	 * WordPress only recommends, and not a full NFKC normalization either: only
	 * the mappings that matter for a host name are applied, so the result stays
	 * predictable for the rest of a text.
	 *
	 * @param string $text The text.
	 *
	 * @return string The text with those characters replaced by their ASCII form.
	 */
	public static function fold_fullwidth_ascii( string $text ): string {
		[ $search, $replace ] = self::fullwidth_ascii_map();

		// A byte-level replacement is safe here: every entry is a complete three-byte
		// UTF-8 sequence, and a lead byte never occurs inside another character, so
		// neither valid text nor malformed bytes can be altered by accident.
		return str_replace( $search, $replace, $text );
	}

	/**
	 * The folded characters and their ASCII counterparts, in matching order.
	 *
	 * Built once per request. The UTF-8 bytes are assembled by hand so that the
	 * map needs neither `mbstring` nor `intl`, which the plugin does not require.
	 *
	 * @return array{0: string[], 1: string[]} The characters to replace and their replacements.
	 */
	private static function fullwidth_ascii_map(): array {
		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$search  = [ "\u{3002}", "\u{FF61}" ];
		$replace = [ '.', '.' ];

		// The fullwidth block U+FF01–U+FF5E mirrors ASCII U+0021–U+007E at a fixed offset.
		for ( $ascii = 0x21; $ascii <= 0x7E; $ascii++ ) {
			$code_point = 0xFEE0 + $ascii;

			$search[]  = pack(
				'C3',
				0xE0 | ( $code_point >> 12 ),
				0x80 | ( ( $code_point >> 6 ) & 0x3F ),
				0x80 | ( $code_point & 0x3F )
			);
			$replace[] = chr( $ascii );
		}

		$map = [ $search, $replace ];

		return $map;
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
