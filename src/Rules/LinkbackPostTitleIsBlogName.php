<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking if the linkback post title is a blog name.
 */
class LinkbackPostTitleIsBlogName extends Base implements SpamReason {
	protected static $slug = 'asb-linkback-post-title-is-blogname';
	protected static $supported_types = [ ContentTypeHelper::LINKBACK_TYPE ];

	public static function verify( $item ) {
		$body      = isset( $item['comment_content'] ) ? $item['comment_content'] : null;
		$blog_name = isset( $item['comment_author'] ) ? $item['comment_author'] : null;
		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return 0;
		}

		return trim( $matches[1] ) === trim( $blog_name ) ? 999 : 0;
	}

	public static function get_name() {
		return __( 'Linkback post title is blog name', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Linkback Post Title', 'spam-reason-text', 'antispam-bee' );
	}
}
