<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\Settings;

/**
 * Contains a function to check whether a post processor is active.
 */
trait IsActive {
	// Todo: Add level to differentiate between rules, post processors, and general
	/**
	 * Returns activation state for post processor.
	 *
	 * @param string $type One of the types defined in AntispamBee\Helpers\ItemTypeHelper::get_types().
	 *
	 * @return mixed|null
	 */
	public static function is_active( $type ) {
		return Settings::get_option( self::get_slug() . '_active', $type );
	}
}
