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

		/*
		 * Hooked to wp_insert_comment rather than comment_post, which fires for
		 * every insertion channel (including the REST API, which never reaches
		 * comment_post). The callback unhooks itself once it has run, so a later,
		 * unrelated comment inserted in the same request cannot inherit this
		 * item's reasons.
		 */
		$callback = null;
		$callback = function ( $comment_id ) use ( $item, &$callback ): void {
			remove_action( 'wp_insert_comment', $callback );

			add_comment_meta(
				$comment_id,
				'antispam_bee_reason',
				implode( ',', (array) $item['asb_reasons'] )
			);
		};
		add_action( 'wp_insert_comment', $callback );

		return $item;
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
