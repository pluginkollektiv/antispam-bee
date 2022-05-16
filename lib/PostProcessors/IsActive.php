<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\Settings;

trait IsActive {
	// Todo: Check what happens if the slug is the same for rules, post processors or custom
	public static function is_active( $type ) {
		return Settings::get_option( self::get_slug() . '_active', $type );
	}
}
