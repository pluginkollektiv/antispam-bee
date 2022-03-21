<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;

class Delete implements PostProcessor, Controllable {

	use IsActive;
	use InitPostProcessor;

	public static function process( $item ) {
		$item['asb_marked_as_delete'] = true;

		return $item;
	}

	public static function get_slug() {
		return 'asb-mark-spam';
	}

	public static function get_label() {
		return __( 'Delete spam', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Delete detected spam instead of marking.', 'antispam-bee' );
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
