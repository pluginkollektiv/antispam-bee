<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\ContentTypeHelper;

/**
 * Checks comment fields based on regular expressions.
 */
class RegexpSpam extends ControllableBase {

	protected static $slug = 'asb-regexp';

	/**
	 * Usage of regexp, also custom
	 */
	public static function verify( $item ) {
		$fields = [
			'ip',
			'host',
			'body',
			'email',
			'author',
			'useragent',
		];

		if ( ContentTypeHelper::COMMENT_TYPE === $item['asb_item_type'] ) {
			$ip        = $item['comment_author_IP'];
			$url       = $item['comment_author_url'];
			$body      = $item['comment_content'];
			$email     = $item['comment_author_email'];
			$author    = $item['comment_author'];
			$useragent = $item['comment_agent'];
			$subject      = array(
				'ip'        => $ip,
				'rawurl'    => $url,
				'host'      => DataHelper::parse_url( $url, 'host' ),
				'body'      => $body,
				'email'     => $email,
				'author'    => $author,
				'useragent' => $useragent,
			);
		}

		if ( ContentTypeHelper::TRACKBACK_TYPE === $item['asb_item_type'] ) {
			$ip        = $item['comment_author_IP'];
			$url       = $item['comment_author_url'];
			$body      = $item['comment_content'];
			$subject      = [
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
				'author' => 'moncler|north face|vuitton|handbag|burberry|outlet|prada|cialis|viagra|maillot|oakley|ralph lauren|ray ban|iphone|プラダ',
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

	public static function get_name() {
		return __( 'Regular Expression', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Use regular expressions', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Predefined and custom patterns by plugin hook', 'antispam-bee' );
	}
}
