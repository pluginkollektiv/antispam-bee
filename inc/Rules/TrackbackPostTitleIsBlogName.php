<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Verifiable;

class TrackbackPostTitleIsBlogName implements Verifiable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		$body      = isset( $data['comment_content'] ) ? $data['comment_content'] : null;
		$blog_name = isset( $data['comment_author'] ) ? $data['comment_author'] : null;
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
