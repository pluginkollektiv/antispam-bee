<?php
/**
 * Save Reasons Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

/**
 * Post processor that is responsible for persisting the reason why something was marked as spam.
 */
class SaveReason extends ControllableBase {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-save-reason';

	/**
	 * Process an item.
	 * Save spam reasons.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( array $item ): array {
		if ( isset( $item['asb_marked_as_delete'] ) && true === $item['asb_marked_as_delete'] ) {
			return $item;
		}

		if ( ! isset( $item['asb_reasons'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();
			return $item;
		}

		add_action(
			'comment_post',
			function ( $comment_id ) use ( $item ) {
				add_comment_meta(
					$comment_id,
					'antispam_bee_reason',
					implode( ',', $item['asb_reasons'] )
				);
			}
		);

		return $item;
	}

	/**
	 * Get element name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Save reasons', 'antispam-bee' );
	}

	/**
	 * Get element label (optional).
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'Save the spam reasons as comment meta', 'antispam-bee' );
	}

	/**
	 * Get element description (optional).
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return __( 'The reasons are displayed in the spam comments list.', 'antispam-bee' );
	}
}
