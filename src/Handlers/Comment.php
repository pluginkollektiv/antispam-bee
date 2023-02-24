<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Rules\Honeypot;
use WP_Comment;

class Comment {
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

		add_filter(
			'preprocess_comment',
			[
				__CLASS__,
				'process',
			],
			1
		);

		add_action( 'transition_comment_status', [ __CLASS__, 'handle_comment_status_changes' ], 10, 3 );

		// Add our manual spam reason to the list of reasons.
		add_filter( 'antispam_bee_additional_spam_reasons', function ( $reasons ) {
			$reasons['asb-marked-manually'] = __( 'Manually', 'antispam-bee' );
			return $reasons;
		} );
	}

	public static function process( $comment ) {
		if ( ContentTypeHelper::is_ping( $comment ) ) {
			return $comment;
		}

		$comment['comment_author_IP'] = IpHelper::get_client_ip();

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			PostProcessors::apply( 'comment', $comment, [ 'asb-empty' ] );

			return $comment;
		}

		$allow_empty_comment = apply_filters( 'allow_empty_comment', false, $comment );
		$comment_content = $comment['comment_content'] ?? '';
		if ( ! $allow_empty_comment && empty( $comment_content ) ) {
			PostProcessors::apply( 'comment', $comment, [ 'asb-empty' ] );

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
					function () {
						return 'spam';
					}
				);

				return $comment;
			}

			status_header( 403 );
			die( 'Spam deleted.' );
		}

		return $comment;
		// todo: Maybe store no-spam-reasons (to discuss)
	}

	/**
	 * React to changes of comment status.
	 *
	 * @param int|string $new_status The new comment status.
	 * @param int|string $old_status The old comment status.
	 * @param WP_Comment $comment    Comment object.
	 */
	public static function handle_comment_status_changes( $new_status, $old_status, $comment ) {
		if ( 'spam' === $new_status && 'spam' !== $old_status ) {
			update_comment_meta( $comment->comment_ID, 'antispam_bee_reason', 'asb-marked-manually' );
			return;
		}

		if ( 'spam' === $old_status && 'spam' !== $new_status ) {
			delete_comment_meta(
				$comment->comment_ID,
				'antispam_bee_reason'
			);
		}
	}
}
