<?php

namespace AntispamBee\PostProcessors;

/**
 * Post processor that marks spam comments so that they are deleted in the end.
 */
class Delete extends ControllableBase {

	protected static $slug            = 'asb-delete-spam';
	protected static $marks_as_delete = true;

	public static function process( $item ) {
		$item['asb_marked_as_delete'] = true;

		return $item;
	}

	public static function get_name() {
		return __( 'Delete spam', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Delete detected spam instead of marking.', 'antispam-bee' );
	}

	public static function get_description() {
		return null;
	}
}
