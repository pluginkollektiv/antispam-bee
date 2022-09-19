<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;

/**
 * Post processor that is responsible for persisting the reason why something was marked as spam.
 */
class SaveReason implements PostProcessor, Controllable {

	use IsActive;
	use InitPostProcessor;

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

	public static function get_slug() {
		return 'asb-save-reason';
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function get_label() {
		return __( 'Save the spam reasons as comment meta', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'The reasons are displayed in the spam comments list.', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}

	public static function marks_as_delete() {
		return false;
	}
}
