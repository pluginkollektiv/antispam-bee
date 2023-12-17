<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Rules\Honeypot;

class Comment extends Reaction {
	public static function init() {
		add_action(
			'init',
			function () {
				if ( ! Honeypot::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
					return;
				}
				Honeypot::precheck();
			}
		);

		parent::init();
	}

	public static function process( $comment ) {
		if ( ! ContentTypeHelper::reaction_is_one_of( $comment, [ ContentTypeHelper::COMMENT_TYPE ], 'comment' ) ) {
			return $comment;
		}

		$comment['comment_author_IP'] = IpHelper::get_client_ip();

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			return self::handle_spam( $comment, [ 'asb-empty' ] );
		}

		$allow_empty_comment = apply_filters( 'allow_empty_comment', false, $comment );
		$comment_content = $comment['comment_content'] ?? '';
		if ( ! $allow_empty_comment && empty( $comment_content ) ) {
			return self::handle_spam( $comment, [ 'asb-empty' ] );
		}

		if ( empty( $comment['comment_author_IP'] ) ) {
			return self::handle_spam( $comment, [ 'asb-empty' ] );
		}

		if ( get_option( 'require_name_email' ) && ( empty( $comment['comment_author_email'] ) || empty( $comment['comment_author'] ) ) ) {
			return self::handle_spam( $comment, [ 'asb-empty' ] );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can post.
		if ( strpos( $request_path, 'wp-comments-post.php' ) === false || empty( $_POST ) ) {
			return $comment;
		}

		parent::process( $comment );

		return $comment;
	}
}
