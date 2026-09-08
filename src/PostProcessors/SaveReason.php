<?php
/**
 * SaveReasons Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

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
	 * Carried between `process()` and `save_reasons()`, because `comment_post`
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

		if ( ! isset( $item['asb_reasons'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();

			return $item;
		}

		self::$reasons = (array) $item['asb_reasons'];

		add_action( 'comment_post', [ self::class, 'save_reasons' ] );

		return $item;
	}

	/**
	 * Persist the spam reasons as comment meta.
	 *
	 * @param int $comment_id ID of the comment that was just saved.
	 *
	 * @return void
	 */
	public static function save_reasons( $comment_id ) {
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
