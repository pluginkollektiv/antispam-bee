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
	protected static $type = 'comment';

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
	 * @param array $reaction Reaction to process.
	 * @return array Processed reaction.
	 */
	public static function process( array $reaction ): array {
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$rules   = new Rules( static::$type );
		$is_spam = $rules->apply( $reaction );

		if ( $is_spam ) {
			return self::handle_spam( $reaction, $rules );
		}

		return $reaction;
	}

	/**
	 * Handle spam.
	 *
	 * @param array $reaction Reaction to handle.
	 * @param Rules $rules    Ruleset to apply.
	 * @return array|never-return Handled reaction (or die, if item was deleted)
	 */
	protected static function handle_spam( array $reaction, Rules $rules ) {
		$item = PostProcessors::apply( static::$type, $reaction, $rules->get_spam_reasons() );
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
