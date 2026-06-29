<?php
/**
 * Sanitization helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use AntispamBee\Admin\Fields\Field;
use AntispamBee\Handlers\GeneralOptions;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;
use AntispamBee\Interfaces\Controllable;

/**
 * Helps by providing reusable sanitizing functions
 */
class Sanitize {

	/**
	 * Sanitizes a checkbox group based on the given values and the valid ones.
	 *
	 * @param mixed $values        Values to sanitize.
	 * @param array $valid_options List of allowed keys.
	 *
	 * @return array Intersection of values and valid options.
	 * @since 3.0.0
	 */
	public static function checkbox_group( $values, array $valid_options ): array {
		if ( ! is_array( $values ) ) {
			return [];
		}

		return array_intersect_key( $values, $valid_options );
	}

	/**
	 * Sanitizes an array of strings to match ISO format.
	 *
	 * @param mixed $codes List of potential ISO codes to sanitize.
	 *
	 * @return array Sanitized ISO codes.
	 * @since 3.0.0
	 */
	public static function iso_codes( $codes ): array {
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

	/**
	 * Sanitize a checkbox value.
	 * Valid values are "on" or null.

	 * @param mixed $value Raw checkbox value.
	 * @return string|null Sanitized value.
	 */
	public static function checkbox( $value ): ?string {
		if ( 'on' === $value ) {
			return $value;
		}

		return null;
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $options Options to sanitize.
	 * @return array Sanitized options.
	 */
	public static function sanitize_options( array $options ): array {
		$current_options = Settings::get_options();
		$options         = ! empty( $options ) ? $options : [];

		$tabs = self::get_tab_slugs();

		foreach ( $tabs as $tab ) {
			if ( ! isset( $options[ $tab ] ) ) {
				$options[ $tab ] = [];
			}
			$sanitized_options       = self::sanitize_controllables( $options, $tab );
			$current_options[ $tab ] = $sanitized_options[ $tab ] ?? [];
		}

		return $current_options;
	}

	/**
	 * Return all valid settings tab slugs derived from registered controllables.
	 *
	 * @return string[]
	 */
	private static function get_tab_slugs(): array {
		$tabs = [ 'general' ];

		foreach ( array_merge( Rules::get_controllables(), PostProcessors::get_controllables() ) as $controllable ) {
			foreach ( $controllable::get_supported_types() as $reaction_type ) {
				$tabs[] = $reaction_type;
			}
		}

		return array_unique( $tabs );
	}

	/**
	 * Sanitize controllable elements.
	 *
	 * @param array  $options Options.
	 * @param string $tab     Settings tab.
	 * @return array Sanitized options.
	 */
	private static function sanitize_controllables( array $options, string $tab ): array {
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

	/**
	 * Call a sanitization callback.
	 *
	 * @param array  $controllable_option Controllable options.
	 * @param array  $options             Options.
	 * @param string $tab                 Settings tab.
	 * @param string $controllable        Controllable element (class name).
	 * @return void
	 */
	private static function call_sanitize_callback( array $controllable_option, array &$options, string $tab, string $controllable ): void {
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
