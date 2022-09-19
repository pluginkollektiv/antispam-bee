<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

abstract class Base implements Controllable {
	protected static $slug;
	protected static $only_custom_options = false;

	/**
	 * @return mixed
	 */
	public static function get_slug() {
		return static::$slug;
	}

	public static function get_label() {}
	public static function get_name() {}
	public static function get_description() {}
	public static function get_options() {
		return null;
	}

	/**
	 * Add setting to list of general options.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'asb_general_options', [ static::class, 'add_general_option' ] );
	}

	/**
	 * Adds setting to general options.
	 *
	 * @param array $options
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public static function add_general_option( $options ) {
		$options[] = static::class;
		return $options;
	}

	public static function is_active( $type ) {
		return Settings::get_option( static::get_slug() . '_active', $type );
	}

	public static function only_print_custom_options() {
		return static::$only_custom_options;
	}
}
