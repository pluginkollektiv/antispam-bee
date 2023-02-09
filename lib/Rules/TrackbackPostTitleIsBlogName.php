<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking if the trackback post title is a blog name.
 */
class TrackbackPostTitleIsBlogName extends Base implements SpamReason {
	protected static $slug            = 'asb-approved-email';
	protected static $supported_types = [ ContentTypeHelper::TRACKBACK_TYPE ];

	public static function verify( $item ) {
		$body      = isset( $item['comment_content'] ) ? $item['comment_content'] : null;
		$blog_name = isset( $item['comment_author'] ) ? $item['comment_author'] : null;
		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return -1;
		}

		return trim( $matches[1] ) === trim( $blog_name ) ? 1 : -1;
	}

	public static function get_name() {
		return __( 'Trackback post title is blog name', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Trackback Post Title', 'spam-reason-text', 'antispam-bee' );
	}
}
