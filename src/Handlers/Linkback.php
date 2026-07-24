<?php
/**
 * Linkback handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;
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
	 * @param array $reaction Linkback to process.
	 *
	 * @return array Processed linkback.
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
}
