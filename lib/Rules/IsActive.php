<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\Settings;

trait IsActive {
	public static function is_active( $type ) {
		return Settings::get_option( self::get_slug() . '_active', $type );
	}
}
