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
			'body',
			'email',
			'author',
			'useragent',
		];

		$subject = [
			'ip'        => $item['ip'] ?? '',
			'rawurl'    => $item['url'] ?? '',
			'host'      => $item['host'] ?? '',
			'body'      => $item['body'] ?? '',
			'email'     => $item['email'] ?? '',
			'author'    => $item['author'] ?? '',
			'useragent' => $item['useragent'] ?? '',
		];

		$patterns = [
			[
				'host'  => '^(www\.)?\d+\w+\.com$',
				'body'  => '^\w+\s\d+$',
				'email' => '@gmail.com$',
			],
			[
				'body'   => '\b[a-z]{30}\b',
				'author' => '\b[a-z]{10}\b',
				'host'   => '\b[a-z]{10}\b',
			],
			[
				'body' => '\<\!.+?mfunc.+?\>',
			],
			[
				'author' => 'moncler|north face|vuitton|handbag|burberry|outlet|prada|cialis|viagra|maillot|oakley|ralph lauren|ray ban|iphone|プラダ|[^\w]?porn[o]?[s]?[^\w]?|[^\w]?pornstar[^\w]?|^20bet$',
			],
			[
				'host' => '^(www\.)?fkbook\.co\.uk$|^(www\.)?nsru\.net$|^(www\.)?goo\.gl$|^(www\.)?bit\.ly$',
			],
			[
				'body' => 'target[t]?ed (visitors|traffic)|viagra|cialis',
			],
			[
				'body' => 'purchase amazing|buy amazing|luxurybrandsale',
			],
			[
				'body'  => 'dating|sex|lotto|pharmacy',
				'email' => '@mail\.ru|@yandex\.',
			],
			[
				'body'   => '^https?:\/\/shorturl\.fm\/[a-zA-Z0-9]{5}$',
				'email'  => '@gmail\.com',
				'author' => '^[A-Z][a-z]+\d{3,4}$',
			],
			[
				'rawurl' => '^http[s]?:\/\/(accounts\.)?binance\.com\/[a-zA-Z-]+\/register(-person)?\?ref=[\w]+',
			],
		];

		$quoted_author = preg_quote( $subject['author'], '/' );
		if ( $quoted_author ) {
			$patterns[] = [
				'body' => sprintf(
					'<a.+?>%s<\/a>$',
					$quoted_author
				),
			];
			$patterns[] = [
				'body' => sprintf(
					'%s https?:.+?$',
					$quoted_author
				),
			];
			$patterns[] = [
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
		 * Each pattern is an array that maps a reaction field (for example `email`,
		 * `author`, `body` or `host`) to a regular expression. A reaction is flagged
		 * as spam when all fields of a single pattern match.
		 *
		 * @since 2.6.7 - commit hash: d7b500c
		 *
		 * @param array $patterns A list of field-to-regular-expression pattern maps.
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

				if ( function_exists( 'iconv' ) ) {
					$converted = iconv( 'utf-8', 'utf-8//TRANSLIT', $subject[ $field ] );
					if ( false !== $converted ) {
						$subject[ $field ] = $converted;
					}
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
