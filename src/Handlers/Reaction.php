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
		add_filter( 'antispam_bee_additional_spam_reasons', [ self::class, 'add_manual_spam_reason' ] );
	}

	/**
	 * Add the "marked manually" reason to the list of spam reasons.
	 *
	 * @param array<string, string> $reasons The registered spam reasons.
	 *
	 * @return array<string, string> The reasons, including the manual one.
	 */
	public static function add_manual_spam_reason( $reasons ) {
		$reasons['asb-marked-manually'] = __( 'Manually', 'antispam-bee' );

		return $reasons;
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
	 * The payload a rule receives is always unslashed, so that a rule can compare
	 * it against stored data — WordPress unslashes a comment before writing it —
	 * without having to know which channel the reaction arrived on.
	 *
	 * @param array<string, mixed> $reaction Raw reaction data.
	 * @param bool                 $slashed  Whether the reaction data is slashed.
	 * @return array<string, mixed> Normalized payload.
	 */
	abstract protected static function build_payload( array $reaction, bool $slashed = true ): array;

	/**
	 * Handle spam.
	 *
	 * @param array<string, mixed> $reaction Reaction to handle.
	 * @param Rules                $rules    Ruleset to apply.
	 *
	 * @return array<string, mixed>|never-return Handled reaction (or die, if item was deleted).
	 */
	protected static function handle_spam( array $reaction, Rules $rules ) {
		if ( ! static::marked_as_delete( static::run_post_processors( $reaction, $rules ) ) ) {
			add_filter( 'pre_comment_approved', [ self::class, 'mark_as_spam' ] );

			return $reaction;
		}

		status_header( 403 );
		die( 'Spam deleted.' );
	}

	/**
	 * Run the post-processors for a reaction that was classified as spam.
	 *
	 * Split out of `handle_spam()` so that channels which cannot mark a reaction
	 * through the `pre_comment_approved` filter — the REST API decides the approval
	 * status before the plugin is given the reaction — still run the same
	 * post-processors.
	 *
	 * @param array<string, mixed> $reaction Reaction that was classified as spam.
	 * @param Rules                $rules    Ruleset that classified it.
	 *
	 * @return array<string, mixed> The post-processed item.
	 */
	protected static function run_post_processors( array $reaction, Rules $rules ): array {
		return PostProcessors::apply( static::$reaction_type, $reaction, $rules->get_spam_reasons() );
	}

	/**
	 * Whether a post-processor marked the item for deletion.
	 *
	 * @param array<string, mixed> $item Post-processed item.
	 *
	 * @return bool Whether the item is to be deleted instead of being stored as spam.
	 */
	protected static function marked_as_delete( array $item ): bool {
		return isset( $item['asb_marked_as_delete'] );
	}

	/**
	 * Force the approval status of a comment to "spam".
	 *
	 * @return string Always `spam`.
	 */
	public static function mark_as_spam() {
		return 'spam';
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
