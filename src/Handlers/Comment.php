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
use WP_Error;

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
		add_action( 'init', [ self::class, 'precheck_honeypot' ] );

		/*
		 * `preprocess_comment` is fired by `wp_new_comment()` only. The REST
		 * controller prepares a comment, asks `wp_allow_comment()` for the approval
		 * status and hands the result straight to `wp_insert_comment()`, so a comment
		 * created through the REST API never passes the filter above and needs its own
		 * entry point.
		 */
		add_filter( 'rest_pre_insert_comment', [ self::class, 'process_rest' ], 1 );

		parent::init();
	}

	/**
	 * Run the honeypot pre-check, if the honeypot rule is active.
	 *
	 * @return void
	 */
	public static function precheck_honeypot() {
		if ( ! Honeypot::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
			return;
		}

		Honeypot::precheck();
	}

	/**
	 * Process a comment.
	 *
	 * @param array<string, mixed> $reaction Comment to process.
	 *
	 * @return array<string, mixed> Processed comment.
	 */
	public static function process( array $reaction ): array {
		$prepared = self::prepare_for_verification( $reaction );

		if ( null === $prepared ) {
			return $reaction;
		}

		parent::process( $prepared );

		return $prepared;
	}

	/**
	 * Verify a comment the REST API is about to insert.
	 *
	 * The REST controller has already determined `comment_approved` through
	 * `wp_allow_comment()` by the time this filter runs, so a spam verdict cannot be
	 * applied through `pre_comment_approved` here and is written to the prepared
	 * comment instead. A comment a post-processor marked for deletion is refused
	 * with a `WP_Error`, which core turns into a proper REST response — the
	 * `die()` the form path uses would emit a non-JSON body.
	 *
	 * @param array<string, mixed>|WP_Error $prepared_comment Prepared comment data, or an error from an earlier filter.
	 *
	 * @return array<string, mixed>|WP_Error The prepared comment, or an error to refuse the insertion.
	 */
	public static function process_rest( $prepared_comment ) {
		if ( ! is_array( $prepared_comment ) ) {
			return $prepared_comment;
		}

		$prepared = self::prepare_for_verification( $prepared_comment );

		if ( null === $prepared ) {
			return $prepared_comment;
		}

		$rules = new Rules( static::$reaction_type );

		if ( ! $rules->apply( static::build_payload( $prepared ) ) ) {
			return $prepared;
		}

		if ( static::marked_as_delete( static::run_post_processors( $prepared, $rules ) ) ) {
			return new WP_Error(
				'rest_comment_spam',
				__( 'Spam deleted.', 'antispam-bee' ),
				[ 'status' => 403 ]
			);
		}

		$prepared['comment_approved'] = 'spam';

		return $prepared;
	}

	/**
	 * Prepare a comment for the spam verification.
	 *
	 * Shared by every channel that creates a comment, so that the decision about
	 * *whether* a comment is verified does not depend on the way it was submitted.
	 *
	 * @param array<string, mixed> $reaction Comment to prepare.
	 *
	 * @return array<string, mixed>|null The prepared comment, or null if it is not verified.
	 */
	private static function prepare_for_verification( array $reaction ): ?array {
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
			return null;
		}

		if ( self::skip_verification( $reaction ) ) {
			return null;
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
