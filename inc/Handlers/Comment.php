<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Rules\Honeypot;

class Comment {
	public static function init() {
		add_action(
			'init',
			function() {
				if ( ! Honeypot::is_active( ItemTypeHelper::COMMENT_TYPE ) ) {
					return;
				}
				Honeypot::precheck();
			}
		);

		add_filter(
			'preprocess_comment',
			[
				__CLASS__,
				'process',
			],
			1
		);
	}

	public static function process( $comment ) {
		$comment['comment_author_IP'] = IpHelper::get_client_ip();

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			PostProcessors::apply( 'comment', $comment, [ 'empty' ] );
			return $comment;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can post.
		if ( strpos( $request_path, 'wp-comments-post.php' ) === false || empty( $_POST ) ) {
			return $comment;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( 'comment' );
		$is_spam = $rules->apply( $comment );

		if ( $is_spam ) {
			$item = PostProcessors::apply( 'comment', $comment, $rules->get_spam_reasons() );
			if ( ! isset( $item['asb_marked_as_delete'] ) ) {
				add_filter(
					'pre_comment_approved',
					function() {
						return 'spam';
					}
				);
			}
		}

		return $comment;
		// todo: Maybe store no-spam-reasons
	}
}
