<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Helpers\Settings;

class DeleteForReasons implements PostProcessor, Controllable {

	use IsActive;
	use InitPostProcessor;

	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && $item['asb_marked_as_delete'] === true ) {
			return $item;
		}

		$reasons = Settings::get_option( $item['asb_item_type'] . '_' . self::get_slug() );
		if ( ! $reasons ) {
			$reasons = [];
		}
		if ( ! empty( array_intersect( $item['asb_reasons'], $reasons ) ) ) {
			$item['asb_marked_as_delete'] = true;
		}

		return $item;
	}

	public static function get_slug() {
		return 'asb-delete-for-reasons';
	}

	public static function get_label() {
		return __( 'Delete comments by spam reasons', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'For multiple selections press Ctrl/CMD', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function marks_as_delete() {
		return true;
	}

	public static function is_active( $type ) {
		return false;
	}
}
