<?php
/**
 * Comment handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Rules\Honeypot;

/**
 * Comment handler.
 */
class Comment extends Reaction {

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public static function init(): void {
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

	/**
	 * Process a comment.
	 *
	 * @param array $comment Comment to process.
	 * @return array Processed comment.
	 */
	public static function process( array $comment ): array {
		/**
		 * Filter processable comment types.
		 *
		 * @param   array   $types List of comment types
		 */
		$comment_types = (array) apply_filters( 'antispam_bee_comment_types', [ '', 'comment', 'review' ] );

		if ( ! ContentTypeHelper::reaction_is_one_of( $comment, $comment_types, 'comment' ) ) {
			return $comment;
		}

		$comment['comment_author_IP'] = IpHelper::get_client_ip();

		$request_uri  = isset( $_SERVER['SCRIPT_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : null;
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			$comment['ab_spam__invalid_request'] = 1;
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
