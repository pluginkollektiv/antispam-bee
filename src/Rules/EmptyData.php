<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Checks for empty data.
 */
class EmptyData extends Base implements SpamReason {
	protected static $slug = 'asb-empty';
	
	public static function verify( $item ) {
		$allow_empty_reaction = apply_filters( 'allow_empty_comment', false, $item );
		$content = $item['comment_content'] ?? '';
		if ( ! $allow_empty_reaction && empty( $content ) ) {
			return 999;
		}

		if ( empty( $item['comment_author_IP'] ) ) {
			return 999;
		}

		if ( $item[ 'reaction_type'] === ContentTypeHelper::COMMENT_TYPE ) {
			if ( get_option( 'require_name_email' ) && ( empty( $comment['comment_author_email'] ) || empty( $comment['comment_author'] ) ) ) {
				return 999;
			}
		}

		if ( $item[ 'reaction_type'] === ContentTypeHelper::LINKBACK_TYPE ) {
			$url  = $linkback['comment_author_url'] ?? '';
			if ( empty( $url ) ) {
				return 999;
			}
		}
	}

	public static function get_name() {
		return _x( 'Empty Data', 'spam-reason-form-name', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Empty Data', 'spam-reason-text', 'antispam-bee' );
	}
}
