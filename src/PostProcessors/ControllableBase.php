<?php
/**
 * Controllable Post Processor Base.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

/**
 * Abstract base class for controllable post processors.
 */
abstract class ControllableBase extends Base implements Controllable {
	/**
	 * Controllable type.
	 *
	 * @var string
	 */
	protected static $type = 'post_processor';

	/**
	 * Only print custom options?
	 *
	 * @var bool
	 */
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

	/**
	 * Get post processor options.
	 *
	 * {@inheritDoc} Default: none.
	 *
	 * @return mixed
	 */
	public static function get_options() {
		return null;
	}

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool
	 */
	public static function only_print_custom_options() {
		return static::$only_print_custom_options;
	}

	/**
	 * Get type of the controllable.
	 *
	 * @return string
	 */
	public static function get_type() {
		return static::$type;
	}

	/**
	 * Get option name.
	 * This will add type and slug prefixes to the short name.
	 *
	 * @param string $name Name suffix.
	 * @return string Corresponding option name
	 */
	public static function get_option_name( $name ) {
		$type        = static::get_type();
		$slug        = static::get_slug();
		$option_name = "{$type}_{$slug}_{$name}";

		return str_replace( '-', '_', $option_name );
	}
}
