<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking if the linkback is from myself.
 *
 * @todo: check on remote server.
 */
class LinkbackFromMyself extends Base implements SpamReason {
	protected static $slug            = 'asb-linkback-from-myself';
	protected static $supported_types = [ ContentTypeHelper::LINKBACK_TYPE ];
	protected static $is_invisible    = true;

	public static function verify( $item ) {
		$url            = isset( $item['comment_author_url'] ) ? $item['comment_author_url'] : null;
		$target_post_id = isset( $item['comment_post_ID'] ) ? $item['comment_post_ID'] : null;
		if ( empty( $url ) || empty( $target_post_id ) ) {
			return 0;
		}

		$url            = $url[0];
		$target_post_id = $target_post_id[0];
		if ( 0 !== strpos( $url, home_url() ) ) {
			return 0;
		}

		$original_post_id = url_to_postid( $url );
		if ( ! $original_post_id ) {
			return 0;
		}

		$post = get_post( $original_post_id );
		if ( ! $post ) {
			return 0;
		}

		$urls        = wp_extract_urls( $post->post_content );
		$url_to_find = get_permalink( $target_post_id );
		if ( ! $url_to_find ) {
			return 0;
		}
		foreach ( $urls as $url ) {
			if ( strpos( $url, $url_to_find ) === 0 ) {
				return -100;
			}
		}

		return 0;
	}

	public static function get_name() {
		return __( 'Linkback from myself', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Linkback from myself', 'spam-reason-text', 'antispam-bee' );
	}
}
