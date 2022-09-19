<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Verifiable;

/**
 * Rule that is responsible for checking if the trackback post title is a blog name.
 */
class TrackbackPostTitleIsBlogName implements Verifiable {

	use InitRule;
	use IsActive;

	// Todo: test
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

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-approved-email';
	}

	public static function is_final() {
		return false;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::TRACKBACK_TYPE ];
	}
}
