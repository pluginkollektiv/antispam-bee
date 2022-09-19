<?php

namespace AntispamBee\Helpers;

use AntispamBee\Admin\Fields\Field;
use AntispamBee\Handlers\GeneralOptions;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;

class Settings {
	protected static $defaults;

	const ANTISPAM_BEE_OPTION_NAME = 'antispam_bee';

	public static function init() {
		add_action(
			'update_option_' . Settings::ANTISPAM_BEE_OPTION_NAME,
			[ __CLASS__, 'update_cache' ],
			1,
			2
		);
	}

	public static function update_cache( $old_value, $value ) {
		wp_cache_set(
			self::ANTISPAM_BEE_OPTION_NAME,
			$value
		);
	}

	/**
	 * Get all plugin options
	 *
	 * @return  array $options Array with option fields.
	 */
	public static function get_options() {
		$options = wp_cache_get( self::ANTISPAM_BEE_OPTION_NAME );
		if ( $options ) {
			return $options;
		}

		$options = get_option( self::ANTISPAM_BEE_OPTION_NAME );
		wp_cache_set( self::ANTISPAM_BEE_OPTION_NAME, $options );

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
		$value_path = str_replace( '-', '_', "$type.$option_name" );
		return self::get_array_value_by_path( $value_path, $options );
	}

	/**
	 * Get value from array by path.
	 *
	 * @param string     $path  Dot-separated path to the wanted value.
	 * @param array      $array
	 *
	 * @return null|mixed
	 */
	private static function get_array_value_by_path( $path, $array ) {
		if ( ! is_array( $array ) ) {
			return null;
		}

		$path_array = self::get_path_parts( $path );
		if ( empty( $path_array ) ) {
			return null;
		}

		$option_value = $array;

		foreach ( $path_array as $path_part ) {
			if ( ! isset( $option_value[ $path_part ] ) ) {
				return null;
			}

			$option_value = $option_value[ $path_part ];
		}

		return $option_value;
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
		$options = get_option( self::ANTISPAM_BEE_OPTION_NAME );

		if ( is_array( $options ) ) {
			$options = array_merge(
				$options,
				$data
			);
		} else {
			$options = $data;
		}

		update_option( self::ANTISPAM_BEE_OPTION_NAME, $options );
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

		if ( ! isset( $_GET['tab'] ) || empty ( $_GET['tab'] ) ) {
			return $_GET['tab'] = 'general';
		}

		$tab = $_GET['tab'];

		$options = ! empty( $options ) ? $options : [ $tab => [] ];

		$sanitized_options = self::sanitize_controllables( $options, $tab );
		$current_options[ $tab ] = $sanitized_options[ $tab ];

		return $current_options;
	}

	private static function sanitize_controllables( $options, $tab ) {
		// Todo: Handle the settings from the `General` tab.
		$controllables = array_merge(
			GeneralOptions::get_controllables( $tab ),
			Rules::get_controllables( $tab ),
			PostProcessors::get_controllables( $tab )
		);

		foreach ( $controllables as $controllable ) {
			$controllable_options = $controllable::get_options();
			$option_path = str_replace( '-', '_', $tab . '.' . $controllable::get_slug() . '_active' );
			$active_state = self::get_array_value_by_path( $option_path, $options );
			$sanitized = Sanitize::checkbox( $active_state );
			if ( ! $sanitized ) {
				self::remove_array_key_by_path( $option_path, $options );
			}

			if ( ! $controllable_options ) {
				continue;
			}

			foreach ( $controllable_options as $controllable_option ) {
				self::call_sanitize_callback( $controllable_option, $options, $tab );
				if (
					isset( $controllable_option['input'] )
					&& $controllable_option['input'] instanceof Field
					&& isset( $controllable_option['input']->get_option()['sanitize'] )
				) {
					self::call_sanitize_callback( $controllable_option['input']->get_option(), $options, $tab );
				}
			}
		}

		return $options;
	}

	private static function call_sanitize_callback( $controllable_option, &$options, $tab ) {
		if ( ! isset( $controllable_option['sanitize'] ) ) {
			return;
		}

		if ( ! isset( $controllable_option['option_name'] ) ) {
			return;
		}

		$option_name = $controllable_option['option_name'];
		$path = "$tab.$option_name";
		$new_value = self::get_array_value_by_path( $path, $options );

		if ( is_callable( $controllable_option['sanitize'] ) ) {
			$sanitized = call_user_func( $controllable_option['sanitize'], $new_value );
			if ( null === $sanitized ) {
				self::remove_array_key_by_path( $path, $options );
				return;
			}

			self::set_array_value_by_path( $path, $sanitized, $options );
		}
	}

	private static function remove_array_key_by_path( $path, &$array ) {
		if ( ! is_array( $array ) ) {
			return $array;
		}

		$path_parts = self::get_path_parts( $path );
		if ( empty( $path_parts ) ) {
			return $array;
		}

		$tmp = &$array;
		$last_key = array_key_last( $path_parts );
		foreach ( $path_parts as $key => $value ) {
			if ( $key === $last_key ) {
				unset( $tmp[ $value ] );
				break;
			}

			if ( isset( $tmp[ $value ] ) ) {
				$tmp = &$tmp[ $value ];
			}
		}
	}

	private static function get_path_parts( $path ) {
		if ( ! is_string( $path ) ) {
			return [];
		}

		$path_parts = explode( '.', $path );
		if ( empty( $path_parts ) ) {
			return [];
		}

		return $path_parts;
	}

	private static function set_array_value_by_path( $path, $sanitized, &$options ) {
		if ( ! is_array( $options ) ) {
			return;
		}

		if ( null === $sanitized ) {
			return;
		}

		$path_parts = self::get_path_parts( $path );
		if ( empty( $path_parts ) ) {
			return;
		}

		$last_key = array_key_last( $path_parts );
		$tmp = &$options;
		foreach ( $path_parts as $key => $value ) {
			if ( $key === $last_key ) {
				$tmp[ $value ] = $sanitized;
				break;
			}

			if ( ! isset( $tmp[ $value ] ) ) {
				$tmp[ $value ] = null;
			}

			$tmp = &$tmp[ $value ];
		}
	}
}
