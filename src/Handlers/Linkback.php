<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;

class Linkback extends Reaction {
	protected static $content_type = 'linkback';

	public static function process( $reaction ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $reaction, [ 'pingback', 'trackback', 'pings' ], '' ) ) {
			return $reaction;
		}

		if ( IgnoreLinkbacks::is_active() ) {
			return $reaction;
		}

		parent::process( $reaction );
	}
}
