<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Helpers\Settings;

/**
 * Post Processor that is responsible for incrementing the spam count and updating the value.
 */
class UpdateSpamCount extends Base {

	protected static $slug = 'asb-update-spam-count';

	public static function process( $item ) {
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
