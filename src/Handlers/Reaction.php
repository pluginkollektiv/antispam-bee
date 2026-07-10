<?php
/**
 * Reaction handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use WP_Comment;

/**
 * Abstract reaction handler.
 */
abstract class Reaction {

	/**
	 * Reaction type (default: "comment").
	 *
	 * @var string
	 */
	protected static $reaction_type = 'comment';

	/**
	 * Initialize the handler.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter(
			'preprocess_comment',
			[ static::class, 'process' ],
			1
		);

		// Add our manual spam reason to the list of reasons.
		add_filter(
			'antispam_bee_additional_spam_reasons',
			function ( $reasons ) {
				$reasons['asb-marked-manually'] = __( 'Manually', 'antispam-bee' );

				return $reasons;
			}
		);
	}

	/**
	 * Always init.
	 *
	 * @return void
	 */
	public static function always_init(): void {
		add_action( 'transition_comment_status', [ __CLASS__, 'handle_comment_status_changes' ], 10, 3 );
	}

	/**
	 * Process a reaction.
	 *
	 * @param array<string, mixed> $reaction Reaction to process.
	 *
	 * @return array<string, mixed> Processed reaction.
	 */
	public static function process( array $reaction ): array {
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( static::$reaction_type );
		$payload = static::build_payload( $reaction );
		$is_spam = $rules->apply( $payload );

		if ( $is_spam ) {
			return self::handle_spam( $reaction, $rules );
		}

		return $reaction;
	}

	/**
	 * Build the normalized payload from the raw reaction data.
	 *
	 * Each reaction is responsible for mapping its own content (e.g. the
	 * WordPress `comment_*` fields) onto the generic payload attributes the
	 * rules consume, and for any helper-based enrichment (IP lookup, host
	 * parsing). The returned payload must include the `reaction_type` attribute.
	 *
	 * @param array<string, mixed> $reaction Raw reaction data.
	 * @return array<string, mixed> Normalized payload.
	 */
	abstract protected static function build_payload( array $reaction ): array;

	/**
	 * Handle spam.
	 *
	 * @param array<string, mixed> $reaction Reaction to handle.
	 * @param Rules                $rules    Ruleset to apply.
	 *
	 * @return array<string, mixed>|never-return Handled reaction (or die, if item was deleted).
	 */
	protected static function handle_spam( array $reaction, Rules $rules ) {
		$item = PostProcessors::apply( static::$reaction_type, $reaction, $rules->get_spam_reasons() );
		if ( ! isset( $item['asb_marked_as_delete'] ) ) {
			add_filter(
				'pre_comment_approved',
				function () {
					return 'spam';
				}
			);

			return $reaction;
		}

		status_header( 403 );
		die( 'Spam deleted.' );
	}

	/**
	 * React to changes of comment status.
	 *
	 * @param int|string $new_status The new comment status.
	 * @param int|string $old_status The old comment status.
	 * @param WP_Comment $comment    Comment object.
	 */
	public static function handle_comment_status_changes( $new_status, $old_status, WP_Comment $comment ): void {
		if ( 'spam' === $new_status && 'spam' !== $old_status ) {
			update_comment_meta( (int) $comment->comment_ID, 'antispam_bee_reason', 'asb-marked-manually' );

			return;
		}

		if ( 'spam' === $old_status && 'spam' !== $new_status ) {
			delete_comment_meta(
				(int) $comment->comment_ID,
				'antispam_bee_reason'
			);
		}
	}
}
