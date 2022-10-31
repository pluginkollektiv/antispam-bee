<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;

/**
 * Rule that is responsible for checking if the trackback post title is a blog name.
 */
class TrackbackPostTitleIsBlogName extends Base {
	protected static $slug = 'asb-approved-email';
	protected static $supported_types = [ ItemTypeHelper::TRACKBACK_TYPE ];

	public static function verify( $item ) {
		$body      = isset( $item['comment_content'] ) ? $item['comment_content'] : null;
		$blog_name = isset( $item['comment_author'] ) ? $item['comment_author'] : null;
		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return - 1;
		}

		return trim( $matches[1] ) === trim( $blog_name ) ? 1 : - 1;
	}

	public static function get_name() {
		return __( 'Trackback post title is blog name', 'antispam-bee' );
	}
}
