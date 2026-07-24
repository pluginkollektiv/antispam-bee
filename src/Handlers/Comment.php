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
		 * Filters the comment types that Antispam Bee processes.
		 *
		 * Reactions whose type is not part of this list are returned unchanged and
		 * skip the spam verification for comments.
		 *
		 * @since 3.0.0
		 *
		 * @param array $types A list of comment types to process.
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

	/**
	 * Build the normalized payload from a comment.
	 *
	 * @param array<string, mixed> $reaction Raw comment data.
	 * @return array<string, mixed> Normalized payload.
	 */
	protected static function build_payload( array $reaction ): array {
		$url = $reaction['comment_author_url'] ?? '';

		return [
			'reaction_type' => static::$reaction_type,
			'ip'            => $reaction['comment_author_IP'] ?? '',
			'url'           => $url,
			'host'          => $url ? DataHelper::parse_url( $url ) : '',
			'body'          => $reaction['comment_content'] ?? '',
			'email'         => $reaction['comment_author_email'] ?? '',
			'author'        => $reaction['comment_author'] ?? '',
			'useragent'     => $reaction['comment_agent'] ?? '',
			'post_id'       => $reaction['comment_post_ID'] ?? null,
		];
	}
}
