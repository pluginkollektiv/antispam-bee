<?php

namespace AntispamBee\Helpers;

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
	 * @since 3.0.0
	 *
	 * @return array Intersection of values and valid options.
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
	 * @since 3.0.0
	 *
	 * @return array
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
}
