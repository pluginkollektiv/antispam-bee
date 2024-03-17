<?php
/**
 * Delete Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

/**
 * Post processor that marks spam comments so that they are deleted in the end.
 */
class Delete extends ControllableBase {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-delete-spam';

	/**
	 * This post processor marks items for deletion.
	 *
	 * @var bool
	 */
	protected static $marks_as_delete = true;

	/**
	 * Process an item, i.e. mark it for deletion.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( $item ) {
		$item['asb_marked_as_delete'] = true;

		return $item;
	}

	/**
	 * Get element name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Delete spam', 'antispam-bee' );
	}

	/**
	 * Get element label (optional).
	 *
	 * @return string|null
	 */
	public static function get_label() {
		return __( 'Delete detected spam instead of marking.', 'antispam-bee' );
	}

	/**
	 * Get element description (optional).
	 *
	 * @return string|null
	 */
	public static function get_description() {
		return null;
	}
}
