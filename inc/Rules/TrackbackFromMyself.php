<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Verifiable;

class TrackbackFromMyself implements Verifiable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		$url = isset( $data['comment_author_url'] ) ? $data['comment_author_url'] : null;
		$target_post_id = isset( $data['comment_post_ID'] ) ? $data['comment_post_ID'] : null;
		if ( empty( $url ) || empty( $target_post_id ) ) {
			return 0;
		}

		$url = $url[0];
		$target_post_id = $target_post_id[0];
		if ( 0 !== strpos( $url, home_url() ) ) {
			return -1;
		}

		$original_post_id = (int) url_to_postid( $url );
		if ( ! $original_post_id ) {
			return -1;
		}

		$post = get_post( $original_post_id );
		if ( ! $post ) {
			return -1;
		}

		$urls        = wp_extract_urls( $post->post_content );
		$url_to_find = get_permalink( $target_post_id );
		if ( ! $url_to_find ) {
			return -1;
		}
		foreach ( $urls as $url ) {
			if ( strpos( $url, $url_to_find ) === 0 ) {
				return 1;
			}
		}
		return -1;
	}

	public static function get_name() {
		return __( 'Trackback from myself', 'antispam-bee' );
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
