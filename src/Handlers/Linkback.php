<?php

namespace AntispamBee\Handlers;

use AntispamBee\GeneralOptions\IgnoreLinkbacks;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\IpHelper;

class Linkback extends Reaction {
	protected static $content_type = 'linkback';

	public static function process( $linkback ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $linkback, [ 'pingback', 'trackback', 'pings' ], '' ) ) {
			return $linkback;
		}

		if ( IgnoreLinkbacks::is_active() ) {
			return $linkback;
		}

		if ( self::is_linkback_title_blog_name( $linkback ) ) {
			return self::handle_spam( $linkback, [ 'asb-title-is-blogname' ] );
		}

		$linkback['comment_author_IP'] = IpHelper::get_client_ip();
		$url  = $linkback['comment_author_url'] ?? '';
		$body = $linkback['comment_content'];

		if ( empty( $url ) || empty( $body ) || empty( $linkback['comment_author_IP'] ) ) {
			return self::handle_spam( $linkback, [ 'asb-empty' ] );
		}

		parent::process( $linkback );
	}

	protected static function is_linkback_title_blog_name( $linkback ) {
		$body = $linkback['comment_content'];
		$blog_name = $linkback['comment_author'];

		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return false;
		}
		return trim( $matches[1] ) === trim( $blog_name );
	}
}
