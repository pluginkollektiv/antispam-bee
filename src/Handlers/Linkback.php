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
	protected static $type = 'linkback';

	/**
	 * Process a linkback.
	 *
	 * @param array $linkback Linkback to process.
	 * @return array Processed linkback.
	 */
	public static function process( array $linkback ): array {
		if ( ! ContentTypeHelper::reaction_is_one_of( $linkback, [ 'pingback', 'trackback', 'pings' ], 'linkback' ) ) {
			return $linkback;
		}

		if ( IgnoreLinkbacks::is_active() ) {
			return $linkback;
		}

		$linkback['comment_author_IP'] = IpHelper::get_client_ip();

		return parent::process( $linkback );
	}
}
