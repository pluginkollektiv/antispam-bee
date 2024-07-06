<?php
/**
 * RegExp Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
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
	 * Content fields using pre-defined and custom regular expressions
	 *
	 * @param array $item Item to verify.
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

		if ( ContentTypeHelper::COMMENT_TYPE === $item['reaction_type'] ) {
			$ip        = $item['comment_author_IP'];
			$url       = $item['comment_author_url'];
			$body      = $item['comment_content'];
			$email     = $item['comment_author_email'];
			$author    = $item['comment_author'];
			$useragent = $item['comment_agent'];
			$subject   = array(
				'ip'        => $ip,
				'rawurl'    => $url,
				'host'      => DataHelper::parse_url( $url, 'host' ),
				'body'      => $body,
				'email'     => $email,
				'author'    => $author,
				'useragent' => $useragent,
			);
		}

		if ( ContentTypeHelper::LINKBACK_TYPE === $item['reaction_type'] ) {
			$ip      = $item['comment_author_IP'];
			$url     = $item['comment_author_url'];
			$body    = $item['comment_content'];
			$subject = [
				'ip'     => $ip,
				'rawurl' => $url,
				'host'   => DataHelper::parse_url( $url, 'host' ),
				'body'   => $body,
				'email'  => '',
				'author' => '',
			];
		}

		if ( ! $subject ) {
			return 0;
		}

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
				'rawurl' => '^http[s]?:\/\/(accounts\.)?binance\.com\/[a-zA-Z-]+\/register(-person)?\?ref=[\w]+',
			],
			[
				'useragent' => 'scrape',
			]
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

				if ( ! isset( $subject[ $field ] ) ) {
					continue;
				}

				$subject[ $field ] = ( function_exists( 'iconv' ) ? iconv( 'utf-8', 'utf-8//TRANSLIT', $subject[ $field ] ) : $subject[ $field ] );

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
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Regular Expression', 'antispam-bee' );
	}

	/**
	 * Get rule label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'Use regular expressions', 'antispam-bee' );
	}

	/**
	 * Get rule description.
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return __( 'Predefined and custom patterns by plugin hook', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string {
		return _x( 'RegExp match', 'spam-reason-text', 'antispam-bee' );
	}
}
