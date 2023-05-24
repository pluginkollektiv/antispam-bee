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

		if ( self::is_linkback_title_blog_name( $reaction ) ) {
			return self::handle_spam( $reaction, [ 'asb-title-is-blogname' ] );
		}

		parent::process( $reaction );
	}

	protected static function is_linkback_title_blog_name( $reaction ) {
		$body = $reaction['comment_content'];
		$blog_name = $reaction['comment_author'];

		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return false;
		}
		return trim( $matches[1] ) === trim( $blog_name );
	}
}
