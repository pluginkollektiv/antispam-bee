<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Settings;

trait IsActive {
	public static function is_active( $type ) {
		return Settings::get_option( $type . '_' . self::get_slug() . '_active' );
	}
}
