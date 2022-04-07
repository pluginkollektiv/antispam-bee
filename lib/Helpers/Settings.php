<?php

namespace AntispamBee\Helpers;

class Settings {
	protected static $defaults;

	const ANTISPAM_BEE_OPTION_NAME = 'antispam_bee';

	public static function init() {}

	/**
	 * Get all plugin options
	 *
	 * @return  array $options Array with option fields.
	 */
	public static function get_options() {
		$options = wp_cache_get( 'antispam_bee' );
		if ( ! $options ) {
			wp_cache_set(
				'antispam_bee',
				$options = get_option( 'antispam_bee' )
			);
		}

		return $options;
	}

	/**
	 * Get single option field
	 *
	 * @param string $option_name Option name.
	 * @param string $type        The type.
	 *
	 * @return  mixed Field value.
	 */
	public static function get_option( $option_name, $type = 'general' ) {
		$options = self::get_options();

		$type = str_replace( '-', '_', $type );
		$option_name = str_replace( '-', '_', $option_name );
		return isset( $options[ $type ][ $option_name ] ) ? $options[ $type ][ $option_name ] : null;
	}

	/**
	 * Update multiple option fields
	 *
	 * @param array $data Array with plugin option fields.
	 *
	 * @since  2.6.1
	 *
	 * @since  0.1
	 */
	public static function update_options( $data ) {
		$options = get_option( 'antispam_bee' );

		if ( is_array( $options ) ) {
			$options = array_merge(
				$options,
				$data
			);
		} else {
			$options = $data;
		}

		update_option( 'antispam_bee', $options );
		wp_cache_set( 'antispam_bee', $options );
	}

	/**
	 * Update single option field
	 *
	 * @param string $field Field name.
	 * @param mixed  $value The Field value.
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public static function update_option( $field, $value ) {
		self::update_options(
			[
				$field => $value,
			]
		);
	}

	/**
	 * Check and return an array key
	 *
	 * @param array  $array Array with values.
	 * @param string $key   Name of the key.
	 *
	 * @return  mixed         Value of the requested key.
	 * @since   2.10.0 Only return `null` if option does not exist.
	 *
	 * @since   2.4.2
	 */
	public static function get_key( $array, $key ) {
		if ( empty( $array ) || empty( $key ) || ! isset( $array[ $key ] ) ) {
			return null;
		}

		return $array[ $key ];
	}

	public static function sanitize( $options ) {
		$current_options = self::get_options();

		// Todo: Call the sanitize functions for the rules/post processors/settings

		if ( ! isset( $_GET['tab'] ) || empty ( $_GET['tab'] ) ) {
			return $current_options;
		}

		$tab = $_GET['tab'];
		$options = ! empty( $options ) ? $options : [ $tab => [] ];
		$current_options[ $tab ] = $options[ $tab ];

		echo '<pre>';
		var_dump( $options, $current_options );
		echo '</pre>';

		return $current_options;
	}
}
