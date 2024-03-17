<?php

namespace AntispamBee\Helpers;

use AntispamBee\Admin\Fields\Field;
use AntispamBee\Handlers\GeneralOptions;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;

/**
 * Helps by providing reusable sanitizing functions
 */
class Sanitize {

	/**
	 * Sanitizes a checkbox group based on the given values and the valid ones.
	 *
	 * @param array $values
	 * @param array $valid_options
	 *
	 * @return array Intersection of values and valid options.
	 * @since 3.0.0
	 */
	public static function checkbox_group( $values, array $valid_options ) {
		if ( ! is_array( $values ) ) {
			return [];
		}

		return array_intersect_key( $values, $valid_options );
	}

	/**
	 * Sanitizes an array of strings to match ISO format.
	 *
	 * @param array $codes
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public static function iso_codes( $codes ) {
		if ( ! is_array( $codes ) ) {
			return [];
		}

		foreach ( $codes as $key => $code ) {
			$code = trim( $code );

			if ( 2 !== strlen( $code ) || ! ctype_alpha( $code ) ) {
				unset( $codes[ $key ] );
				continue;
			}

			$codes[ $key ] = $code;
		}

		return $codes;
	}

	public static function checkbox( $value ) {
		if ( 'on' === $value ) {
			return $value;
		}

		return null;
	}

	public static function sanitize_options( $options ) {
		$current_options = Settings::get_options();

		if ( ! isset( $_GET['tab'] ) || empty( $_GET['tab'] ) ) {
			return $_GET['tab'] = 'general';
		}

		$tab = $_GET['tab'];

		$options = ! empty( $options ) ? $options : [ $tab => [] ];

		$sanitized_options       = self::sanitize_controllables( $options, $tab );
		$current_options[ $tab ] = $sanitized_options[ $tab ];

		return $current_options;
	}

	private static function sanitize_controllables( $options, $tab ) {
		$controllables = array_merge(
			GeneralOptions::get_controllables( $tab ),
			Rules::get_controllables( $tab ),
			PostProcessors::get_controllables( $tab )
		);

		foreach ( $controllables as $controllable ) {
			$option_path  = str_replace( '-', '_', $tab . '.' . $controllable::get_option_name( 'active' ) );
			$active_state = Settings::get_array_value_by_path( $option_path, $options );
			$sanitized    = self::checkbox( $active_state );
			if ( ! $sanitized ) {
				Settings::remove_array_key_by_path( $option_path, $options );
			}

			$controllable_options = $controllable::get_options();
			if ( ! $controllable_options ) {
				continue;
			}

			foreach ( $controllable_options as $controllable_option ) {
				if ( isset( $controllable_option['valid_for'] ) && $controllable_option['valid_for'] !== $tab ) {
					continue;
				}

				self::call_sanitize_callback( $controllable_option, $options, $tab, $controllable );
				if (
					isset( $controllable_option['input'] )
					&& $controllable_option['input'] instanceof Field
					&& isset( $controllable_option['input']->get_option()['sanitize'] )
				) {
					self::call_sanitize_callback( $controllable_option['input']->get_option(), $options, $tab, $controllable );
				}
			}
		}

		return $options;
	}

	private static function call_sanitize_callback( $controllable_option, &$options, $tab, $controllable ) {
		if ( ! isset( $controllable_option['sanitize'] ) ) {
			return;
		}

		if ( ! isset( $controllable_option['option_name'] ) ) {
			return;
		}

		$option_name = $controllable::get_option_name( $controllable_option['option_name'] );
		$path        = str_replace( '-', '_', "$tab.$option_name" );
		$new_value   = Settings::get_array_value_by_path( $path, $options );

		if ( is_callable( $controllable_option['sanitize'] ) ) {
			$sanitized = call_user_func( $controllable_option['sanitize'], $new_value );
			if ( null === $sanitized ) {
				Settings::remove_array_key_by_path( $path, $options );

				return;
			}

			Settings::set_array_value_by_path( $path, $sanitized, $options );
		}
	}
}
