<?php
/**
 * Update Spam Count Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Helpers\Settings;

/**
 * Post Processor that is responsible for incrementing the spam count and updating the value.
 */
class UpdateSpamCount extends Base {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-update-spam-count';

	/**
	 * Process an item.
	 * Increment the spam counter by 1.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( array $item ): array {
		if ( ! Statistics::is_active() ) {
			return $item;
		}

		Settings::update_option(
			'spam_count',
			intval( Settings::get_option( 'spam_count', '' ) + 1 )
		);

		return $item;
	}
}
