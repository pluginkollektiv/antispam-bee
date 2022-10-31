<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

abstract class ControllableBase extends Base implements Controllable {
	protected static $only_print_custom_options = false;

	protected static $type = 'rule';

	public static function is_active( $type ) {
		return Settings::get_option( static::get_option_name( 'active' ), $type );
	}

	public static function get_options() {
		return null;
	}

	public static function only_print_custom_options() {
		return static::$only_print_custom_options;
	}

	public static function get_type() {
		return static::$type;
	}

	public static function get_option_name( $name ) {
		$type = static::get_type();
		$slug = static::get_slug();
		$option_name = "{$type}_{$slug}_{$name}";
		return str_replace( '-', '_', $option_name );
	}
}
