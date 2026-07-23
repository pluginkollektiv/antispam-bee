<?php
/**
 * Linkback handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\IpHelper;

/**
 * Linkback handler.
 */
class Linkback extends Reaction {

	/**
	 * Reaction type.
	 *
	 * @var string
	 */
	protected static $reaction_type = 'linkback';

	/**
	 * Process a linkback.
	 *
	 * @param array<string, mixed> $reaction Linkback to process.
	 *
	 * @return array<string, mixed> Processed linkback.
	 */
	public static function process( array $reaction ): array {
		if ( ! ContentTypeHelper::reaction_is_one_of( $reaction, [ 'pingback', 'trackback', 'pings' ], 'linkback' ) ) {
			return $reaction;
		}

		if ( IgnoreLinkbacks::is_active() ) {
			return $reaction;
		}

		$reaction['comment_author_IP'] = IpHelper::get_client_ip();

		return parent::process( $reaction );
	}

	/**
	 * Build the normalized payload from a linkback.
	 *
	 * Linkback fields can arrive as arrays; they are normalized to scalars so
	 * rules can treat every payload value uniformly.
	 *
	 * @param array<string, mixed> $reaction Raw linkback data.
	 * @return array<string, mixed> Normalized payload.
	 */
	protected static function build_payload( array $reaction ): array {
		$url = self::scalar( $reaction['comment_author_url'] ?? '' );

		return [
			'reaction_type' => static::$reaction_type,
			'ip'            => self::scalar( $reaction['comment_author_IP'] ?? '' ),
			'url'           => $url,
			'host'          => $url ? DataHelper::parse_url( $url ) : '',
			'body'          => self::scalar( $reaction['comment_content'] ?? '' ),
			'email'         => '',
			'author'        => self::scalar( $reaction['comment_author'] ?? '' ),
			'useragent'     => '',
			'post_id'       => self::scalar( $reaction['comment_post_ID'] ?? null ),
		];
	}

	/**
	 * Normalize a possibly array-valued linkback field to a scalar.
	 *
	 * @param mixed $value Raw field value.
	 * @return mixed First element for arrays, the value otherwise.
	 */
	private static function scalar( $value ) {
		if ( is_array( $value ) ) {
			return reset( $value );
		}

		return $value;
	}
}
