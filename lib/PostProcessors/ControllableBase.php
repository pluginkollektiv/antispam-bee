<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

abstract class ControllableBase extends Base implements Controllable {

	protected static $type = 'post_processor';

	protected static $only_print_custom_options = false;

	/**
	 * Returns activation state for post processor.
	 *
	 * @param string $type One of the types defined in AntispamBee\Helpers\ItemTypeHelper::get_types().
	 *
	 * @return mixed|null
	 */
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
