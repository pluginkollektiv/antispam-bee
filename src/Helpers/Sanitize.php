<?php
/**
 * Sanitization helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use AntispamBee\Admin\Fields\Field;
use AntispamBee\Admin\Fields\FieldOptions;
use AntispamBee\Handlers\GeneralOptions;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;
use AntispamBee\Interfaces\Controllable;

/**
 * Helps by providing reusable sanitizing functions.
 */
class Sanitize {

	/**
	 * Sanitize a checkbox group based on the given values and the valid ones.
	 *
	 * @param mixed                   $values        Values to sanitize.
	 * @param array<array-key, mixed> $valid_options A list of allowed keys.
	 *
	 * @return array<array-key, mixed> Intersection of values and valid options.
	 */
	public static function checkbox_group( $values, array $valid_options ): array {
		if ( ! is_array( $values ) ) {
			return [];
		}

		return array_intersect_key( $values, $valid_options );
	}

	/**
	 * Sanitize an array of strings to match ISO format.
	 *
	 * @param array<array-key, string> $codes A list of potential ISO codes to sanitize.
	 *
	 * @return array<array-key, string> Sanitized ISO codes.
	 */
	public static function iso_codes( array $codes ): array {
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
	 * Sanitize the options.
	 *
	 * Registered as the `sanitize_callback` of the settings API, so WordPress
	 * passes whatever was posted. The settings screen submits an array, but the
	 * request is not required to: a scalar posted for the option would reach a
	 * declared `array` parameter and raise a `TypeError`. Sanitizing untrusted
	 * input is this method's purpose, so it accepts anything and discards what it
	 * cannot use, leaving the stored options untouched.
	 *
	 * @param mixed $options Options to sanitize.
	 *
	 * @return array<string, array<string, string>> Sanitized options.
	 */
	public static function sanitize_options( $options ): array {
		$current_options = Settings::get_options();
		$options         = is_array( $options ) ? $options : [];

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
	 * @return string[] A list of valid settings tab slugs.
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
	 * @param array<string, mixed> $options Options.
	 * @param string               $tab     Settings tab.
	 *
	 * @return array<string, mixed> Sanitized options.
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

			$controllable_options = (array) $controllable::get_options();
			if ( ! $controllable_options ) {
				continue;
			}

			foreach ( $controllable_options as $controllable_option ) {
				$valid_for = $controllable_option->get_valid_for();
				if ( '' !== $valid_for && $valid_for !== $tab ) {
					continue;
				}

				self::call_sanitize_callback( $controllable_option, $options, $tab, $controllable );

				$input = $controllable_option->get_input();
				if ( $input instanceof Field ) {
					$input_options = $input->get_option();
					if ( $input_options->get_sanitize() !== null ) {
						self::call_sanitize_callback( $input_options, $options, $tab, $controllable );
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Sanitize a checkbox value.
	 * Valid values are "on" or null.
	 *
	 * @param mixed $value Raw checkbox value.
	 *
	 * @return string|null Sanitized value.
	 */
	public static function checkbox( $value ): ?string {
		if ( 'on' === $value ) {
			return $value;
		}

		return null;
	}

	/**
	 * Call a sanitization callback.
	 *
	 * @param FieldOptions         $controllable_option Controllable field options.
	 * @param array<string, mixed> $options             Options.
	 * @param string               $tab                 Settings tab.
	 * @param string               $controllable        Controllable element (class name).
	 *
	 * @phpstan-param class-string<Controllable> $controllable
	 *
	 * @return void
	 */
	private static function call_sanitize_callback( FieldOptions $controllable_option, array &$options, string $tab, string $controllable ): void {
		$sanitize    = $controllable_option->get_sanitize();
		$option_name = $controllable_option->get_option_name();

		if ( null === $sanitize || '' === $option_name ) {
			return;
		}

		$full_name   = $controllable::get_option_name( $option_name );
		$option_path = str_replace( '-', '_', "$tab.$full_name" );
		$new_value   = Settings::get_array_value_by_path( $option_path, $options );

		$sanitized = call_user_func( $sanitize, $new_value );
		if ( null === $sanitized ) {
			Settings::remove_array_key_by_path( $option_path, $options );

			return;
		}

		Settings::set_array_value_by_path( $option_path, $sanitized, $options );
	}
}
