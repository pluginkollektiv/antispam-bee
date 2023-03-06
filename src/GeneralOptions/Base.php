<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

abstract class Base implements Controllable {
	protected static $type = 'general';
	protected static $slug;
	protected static $only_custom_options = false;

	/**
	 * @return mixed
	 */
	public static function get_slug() {
		return static::$slug;
	}

	public static function get_label() {
	}

	public static function get_name() {
	}

	public static function get_description() {
	}

	public static function get_options() {
		return null;
	}

	/**
	 * Add setting to list of general options.
	 *
	 * @return void
	 * @since 3.0.0
	 *
	 */
	public static function init() {
		add_filter( 'antispam_bee_general_options', [ static::class, 'add_general_option' ] );
	}

	/**
	 * Adds setting to general options.
	 *
	 * @param array $options
	 *
	 * @return mixed
	 * @since 3.0.0
	 *
	 */
	public static function add_general_option( $options ) {
		$options[] = static::class;

		return $options;
	}

	public static function is_active( $type = 'general' ) {
		return Settings::get_option( static::get_option_name( 'active' ), $type );
	}

	public static function only_print_custom_options() {
		return static::$only_custom_options;
	}

	public static function get_supported_types() {
		return [ 'general' ];
	}

	public static function get_type() {
		return static::$type;
	}

	public static function get_option_name( $name ) {
		$type        = static::get_type();
		$slug        = static::get_slug();
		$option_name = "{$type}_{$slug}_{$name}";

		return str_replace( '-', '_', $option_name );
	}
}
