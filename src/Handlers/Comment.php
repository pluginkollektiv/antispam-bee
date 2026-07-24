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
	 * @param array<string, mixed> $reaction Comment to process.
	 *
	 * @return array<string, mixed> Processed comment.
	 */
	public static function process( array $reaction ): array {
		/**
		 * Filter processable comment types.
		 *
		 * @param array $types A list of comment types.
		 */
		$comment_types = (array) apply_filters( 'antispam_bee_comment_types', [ '', 'comment', 'review' ] );

		if ( ! ContentTypeHelper::reaction_is_one_of( $reaction, $comment_types, 'comment' ) ) {
			return $reaction;
		}

		$reaction['comment_author_IP'] = IpHelper::get_client_ip();

		$request_uri  = isset( $_SERVER['SCRIPT_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		$request_path = DataHelper::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			$reaction['ab_spam__invalid_request'] = 1;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can post.
		if ( strpos( $request_path, 'wp-comments-post.php' ) === false || empty( $_POST ) ) {
			return $reaction;
		}

		parent::process( $reaction );

		return $reaction;
	}
}
