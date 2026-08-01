<?php
/**
 * RegExp Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks comment fields based on regular expressions.
 */
class RegexpSpam extends ControllableBase implements SpamReason {


	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-regexp';

	/**
	 * Verify an item.
	 *
	 * Content fields using pre-defined and custom regular expressions.
	 *
	 * Handled payload attributes: `ip`, `url`, `host`, `body`, `email`,
	 * `author`, `useragent`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @phpstan-param array{
	 *     reaction_type?: string,
	 *     ip?: string,
	 *     url?: string,
	 *     host?: string,
	 *     body?: string,
	 *     email?: string,
	 *     author?: string,
	 *     useragent?: string,
	 * } $item
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$fields = [
			'ip',
			'host',
			'rawurl',
			'body',
			'email',
			'author',
			'useragent',
		];

		/*
		 * Repair the payload up front, before anything is built from it. The author
		 * is spliced into the patterns below, and a pattern that is not valid UTF-8
		 * makes the `u` modifier fail to compile — which raises a warning instead of
		 * simply not matching.
		 */
		$subject = array_map(
			[ self::class, 'repair_utf8' ],
			[
				'ip'        => $item['ip'] ?? '',
				'rawurl'    => $item['url'] ?? '',
				'host'      => $item['host'] ?? '',
				'body'      => $item['body'] ?? '',
				'email'     => $item['email'] ?? '',
				'author'    => $item['author'] ?? '',
				'useragent' => $item['useragent'] ?? '',
			]
		);

		$patterns = [
			'asb-gmail-numeric-domain'       => [
				'host'  => '^(www\.)?\d+\w+\.com$',
				'body'  => '^\w+\s\d+$',
				'email' => '@gmail.com$',
			],
			'asb-gibberish-strings'          => [
				'body'   => '\b[a-z]{30}\b',
				'author' => '\b[a-z]{10}\b',
				'host'   => '\b[a-z]{10}\b',
			],
			'asb-mfunc-injection'            => [
				'body' => '\<\!.+?mfunc.+?\>',
			],
			'asb-spam-keywords-author'       => [
				'author' => 'moncler|north face|vuitton|handbag|burberry|outlet|prada|cialis|viagra|maillot|oakley|ralph lauren|ray ban|iphone|プラダ|[^\w]?porn[o]?[s]?[^\w]?|[^\w]?pornstar[^\w]?|^20bet$',
			],
			'asb-known-spam-hosts'           => [
				'host' => '^(www\.)?fkbook\.co\.uk$|^(www\.)?nsru\.net$|^(www\.)?goo\.gl$|^(www\.)?bit\.ly$',
			],
			'asb-traffic-and-pharma-body'    => [
				'body' => 'target[t]?ed (visitors|traffic)|viagra|cialis',
			],
			'asb-luxury-brand-sale-body'     => [
				'body' => 'purchase amazing|buy amazing|luxurybrandsale',
			],
			'asb-adult-pharma-russian-email' => [
				'body'  => 'dating|sex|lotto|pharmacy',
				'email' => '@mail\.ru|@yandex\.',
			],
			'asb-shorturl-fm-link-only-body' => [
				'body'   => '^https?:\/\/shorturl\.fm\/[a-zA-Z0-9]{5}$',
				'email'  => '@gmail\.com',
				'author' => '^[A-Z][a-z]+\d{3,4}$',
			],
			'asb-binance-referral-url'       => [
				'rawurl' => '^http[s]?:\/\/(accounts\.)?binance\.com\/[a-zA-Z-]+\/register(-person)?\?ref=[\w]+',
			],
		];

		$quoted_author = preg_quote( $subject['author'], '/' );
		if ( $quoted_author ) {
			$patterns['asb-author-name-as-link-text']    = [
				'body' => sprintf(
					'<a.+?>%s<\/a>$',
					$quoted_author
				),
			];
			$patterns['asb-author-name-followed-by-url'] = [
				'body' => sprintf(
					'%s https?:.+?$',
					$quoted_author
				),
			];
			$patterns['asb-author-name-as-host']         = [
				'email'  => '@gmail.com$',
				'author' => '^[a-z0-9-\.]+\.[a-z]{2,6}$',
				'host'   => sprintf(
					'^%s$',
					$quoted_author
				),
			];
		}

		/**
		 * Filters the regular expression patterns used to detect spam.
		 *
		 * Each entry is a map of subject field (`ip`, `host`, `rawurl`, `body`, `email`,
		 * `author`, `useragent`) to a regular expression without delimiters. All fields of
		 * an entry must match for the reaction to be flagged as spam. Array keys are stable
		 * identifiers, so single default patterns can be modified or removed. The built-in
		 * ones are prefixed with `asb-`.
		 *
		 * @since 2.5.2
		 * @since 3.0.0 Patterns are keyed by a stable identifier instead of a numeric index.
		 *
		 * @param array<string, array<string, string>> $patterns Patterns, keyed by identifier.
		 *
		 * @return array<string, array<string, string>> Filtered patterns.
		 */
		$patterns = apply_filters(
			'antispam_bee_patterns',
			$patterns
		);

		if ( ! $patterns ) {
			return 0;
		}

		foreach ( $patterns as $pattern ) {
			$hits = [];

			foreach ( $pattern as $field => $regexp ) {
				if ( empty( $field ) || ! in_array( $field, $fields, true ) || empty( $regexp ) ) {
					continue;
				}

				/*
				 * Patterns reach this point through the `antispam_bee_patterns` filter
				 * as well, so a third party can supply one that is not valid UTF-8.
				 * Skip it rather than let the compilation warning through.
				 */
				if ( ! self::is_valid_utf8( (string) $regexp ) ) {
					continue;
				}

				if ( empty( $subject[ $field ] ) ) {
					continue;
				}

				if ( preg_match( '/' . $regexp . '/isu', $subject[ $field ] ) ) {
					$hits[ $field ] = true;
				}
			}

			if ( count( $hits ) === count( $pattern ) ) {
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Check whether a string is well-formed UTF-8.
	 *
	 * An empty pattern with the `u` modifier reports malformed input through its
	 * return value instead of a warning, which is what makes it usable as a guard
	 * in front of the patterns that would otherwise fail to compile.
	 *
	 * @param string $value Value to check.
	 *
	 * @return bool Whether the value is valid UTF-8.
	 */
	private static function is_valid_utf8( string $value ): bool {
		return '' === $value || 1 === preg_match( '//u', $value );
	}

	/**
	 * Drop byte sequences that are not valid UTF-8.
	 *
	 * Comment payloads are not guaranteed to be well-formed: a client can post an
	 * overlong encoding or a truncated multibyte character, and that value then
	 * travels into both the subjects and the patterns built from them.
	 *
	 * `iconv()` with `//TRANSLIT` is deliberately not used, because it raises a
	 * warning of its own and returns `false` for exactly this input.
	 *
	 * @param mixed $value Value to repair.
	 *
	 * @return string Repaired value, or an empty string when it cannot be repaired.
	 */
	private static function repair_utf8( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';

		if ( self::is_valid_utf8( $value ) ) {
			return $value;
		}

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$repaired = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
		} elseif ( function_exists( 'iconv' ) ) {
			$repaired = iconv( 'utf-8', 'utf-8//IGNORE', $value );
		} else {
			$repaired = false;
		}

		if ( ! is_string( $repaired ) || ! self::is_valid_utf8( $repaired ) ) {
			return '';
		}

		return $repaired;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Regular Expression', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Use regular expressions', 'antispam-bee' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string|null The rule description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'Predefined and custom patterns by plugin hook', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'RegExp match', 'spam-reason-text', 'antispam-bee' );
	}
}
