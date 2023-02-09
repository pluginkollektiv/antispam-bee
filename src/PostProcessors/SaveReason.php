<?php

namespace AntispamBee\PostProcessors;

/**
 * Post processor that is responsible for persisting the reason why something was marked as spam.
 */
class SaveReason extends ControllableBase {
	protected static $slug = 'asb-save-reason';

	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && $item['asb_marked_as_delete'] === true ) {
			return $item;
		}

		if ( ! isset( $item['asb_reasons'] ) ) {
			return $item['asb_post_processors_failed'][] = self::get_slug();
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

	public static function get_name() {
		return __( 'Save reasons', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Save the spam reasons as comment meta', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'The reasons are displayed in the spam comments list.', 'antispam-bee' );
	}
}
