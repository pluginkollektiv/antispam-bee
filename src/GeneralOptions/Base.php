<?php
/**
 * General Options base class.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;


/**
 * Abstract base class for general options.
 */
abstract class Base implements Controllable {

	/**
	 * Option type.
	 *
	 * @var string
	 */
	protected static $type = 'general';

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug;

	/**
	 * Only show custom options?
	 *
	 * @var bool
	 */
	protected static $only_custom_options = false;

	/**
	 * Get option slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return static::$slug;
	}

	/**
	 * Get options.
	 *
	 * {@inheritDoc}
	 *
	 * @return mixed
	 */
	public static function get_options() {
		return null;
	}

	/**
	 * Add setting to list of general options.
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public static function init() {
		add_filter( 'antispam_bee_general_options', [ static::class, 'add_general_option' ] );
	}

	/**
	 * Adds setting to general options.
	 *
	 * @param array $options Currently registered options.
	 *
	 * @return array Updated options.
	 * @since 3.0.0
	 */
	public static function add_general_option( $options ) {
		$options[] = static::class;

		return $options;
	}

	/**
	 * Returns activation state for this option.
	 *
	 * @param string $type One of the types defined in AntispamBee\Helpers\ItemTypeHelper::get_types().
	 *
	 * @return mixed|null
	 */
	public static function is_active( $type = 'general' ) {
		return Settings::get_option( static::get_option_name( 'active' ), $type );
	}

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool
	 */
	public static function only_print_custom_options() {
		return static::$only_custom_options;
	}

	/**
	 * Get a list of supported types.
	 *
	 * @return string[]
	 */
	public static function get_supported_types() {
		return [ 'general' ];
	}

	/**
	 * Get type of the option.
	 *
	 * @return string
	 */
	public static function get_type() {
		return static::$type;
	}

	/**
	 * Get option name.
	 * Append type and slug to the given name.
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
