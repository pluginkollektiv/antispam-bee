<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\IpHelper;

class Linkback extends Reaction {
	protected static $type = 'linkback';

	public static function process( $linkback ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $linkback, [ ContentTypeHelper::LINKBACK_TYPE ], 'linkback' ) ) {
			return $linkback;
		}

		if ( IgnoreLinkbacks::is_active() ) {
			return $linkback;
		}

		$linkback['comment_author_IP'] = IpHelper::get_client_ip();

		parent::process( $linkback );
	}
}
