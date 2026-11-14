<?php
/**
 * SaveReasons Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use WP_Comment;

/**
 * Post-processor that is responsible for persisting the reason why something was marked as spam.
 */
class SaveReason extends ControllableBase {

	/**
	 * Post-processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-save-reason';

	/**
	 * The spam reasons to persist once the comment has been saved.
	 *
	 * Carried between `process()` and `save_reasons()`, because `wp_insert_comment`
	 * hands the callback nothing but the comment ID.
	 *
	 * @var array<int, string>
	 */
	private static $reasons = [];

	/**
	 * Process an item.
	 * Save spam reasons.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
	 */
	public static function process( array $item ): array {
		if ( isset( $item['asb_marked_as_delete'] ) && true === $item['asb_marked_as_delete'] ) {
			return $item;
		}

		// The reason is stored as comment meta, so the item has to become a comment.
		if ( ! isset( $item['comment_post_ID'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();

			return $item;
		}

		if ( ! isset( $item['asb_reasons'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();

			return $item;
		}

		self::$reasons = (array) $item['asb_reasons'];

		add_action( 'wp_insert_comment', [ self::class, 'save_reasons' ], 10, 2 );

		return $item;
	}

	/**
	 * Persist the spam reasons as comment meta.
	 *
	 * The comment the reasons belong to is always inserted as spam, so any other
	 * comment inserted before it in the same request is skipped.
	 *
	 * @param int        $comment_id ID of the comment that was just saved.
	 * @param WP_Comment $comment    The comment that was just saved.
	 *
	 * @return void
	 */
	public static function save_reasons( $comment_id, $comment ) {
		if ( 'spam' !== $comment->comment_approved ) {
			return;
		}

		remove_action( 'wp_insert_comment', [ self::class, 'save_reasons' ], 10 );

		add_comment_meta(
			$comment_id,
			'antispam_bee_reason',
			implode( ',', self::$reasons )
		);
	}

	/**
	 * Get the element name.
	 *
	 * @return string The name.
	 */
	public static function get_name(): string {
		return __( 'Save reasons', 'antispam-bee' );
	}

	/**
	 * Get the element label (optional).
	 *
	 * @return string|null The label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Save the spam reasons as comment meta', 'antispam-bee' );
	}

	/**
	 * Get the element description (optional).
	 *
	 * @return string|null The description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'The reasons are displayed in the spam comments list.', 'antispam-bee' );
	}
}
