<?php

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

class RegexpSpam implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	/**
	 * Usage of regexp, also custom
	 */
	public static function verify( $data ) {
		$fields = [
			'ip',
			'host',
			'body',
			'email',
			'author',
			'useragent',
		];

		$comment = $data;

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

		$quoted_author = preg_quote( $comment['author'], '/' );
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
			return false;
		}

		foreach ( $patterns as $pattern ) {
			$hits = [];

			foreach ( $pattern as $field => $regexp ) {
				if ( empty( $field ) || ! in_array( $field, $fields, true ) || empty( $regexp ) ) {
					continue;
				}

				$comment[ $field ] = ( function_exists( 'iconv' ) ? iconv( 'utf-8', 'utf-8//TRANSLIT', $comment[ $field ] ) : $comment[ $field ] );

				if ( empty( $comment[ $field ] ) ) {
					continue;
				}

				if ( preg_match( '/' . $regexp . '/isu', $comment[ $field ] ) ) {
					$hits[ $field ] = true;
				}
			}

			if ( count( $hits ) === count( $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	public static function get_name() {
		return __( 'Regular Expression', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1;
	}

	public static function get_slug() {
		return 'asb-regexp';
	}

	public static function is_final() {
		return false;
	}

	public static function get_supported_types() {
		return [ 'comment', 'trackback' ];
	}

	public static function get_label() {
		__( 'Use regular expressions', 'antispam-bee' );
	}

	public static function get_description() {
		__( 'Predefined and custom patterns by plugin hook', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}

	public static function is_active() {
		return false;
	}
}
