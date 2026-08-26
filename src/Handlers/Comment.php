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

		if ( self::skip_verification( $reaction ) ) {
			return $reaction;
		}

		/*
		 * Only filled in when the caller did not supply one. Core does the same
		 * (`wp_new_comment()` falls back to REMOTE_ADDR only for an absent value), so
		 * importers, migrations and plugins that pass a historical or explicit IP keep
		 * it. An unusable REMOTE_ADDR is left alone rather than written back as an
		 * empty string: `IpHelper::get_client_ip()` returns '' for anything
		 * `FILTER_VALIDATE_IP` rejects, such as a zone-scoped IPv6 address, and storing
		 * that would drop an IP core would have kept.
		 */
		if ( empty( $reaction['comment_author_IP'] ) ) {
			$client_ip = IpHelper::get_client_ip();

			if ( '' !== $client_ip ) {
				$reaction['comment_author_IP'] = $client_ip;
			}
		}

		parent::process( $reaction );

		return $reaction;
	}

	/**
	 * Whether the spam verification is skipped for this comment.
	 *
	 * @param array<string, mixed> $reaction Comment to process.
	 *
	 * @return bool Whether to skip the verification.
	 */
	private static function skip_verification( array $reaction ): bool {
		/**
		 * Filters whether Antispam Bee skips the spam verification for a comment.
		 *
		 * Every caller of `wp_new_comment()` reaches this handler through
		 * `preprocess_comment`, not just the front-end comment form, so the
		 * decision is made from the request context rather than from the script
		 * that happens to be executing.
		 *
		 * @since 3.0.0
		 *
		 * @param bool  $skip     Whether to skip the verification.
		 * @param array $reaction The comment being processed.
		 */
		return (bool) apply_filters( 'antispam_bee_skip_comment_verification', self::is_trusted_context(), $reaction );
	}

	/**
	 * Whether the comment originates from a context that is not a public submission.
	 *
	 * Comments inserted while WordPress installs, while an importer runs, from
	 * WP-CLI, or by a moderator working in the admin are not visitor input and are
	 * therefore not verified.
	 *
	 * @return bool Whether the current request is a trusted context.
	 */
	private static function is_trusted_context(): bool {
		if ( wp_installing() ) {
			return true;
		}

		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		return is_admin() && current_user_can( 'moderate_comments' );
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
