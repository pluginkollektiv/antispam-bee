<?php

namespace AntispamBee\Helpers;

class DataHelper {

	public static function get_values_by_keys( $keys, $data ) {
		$results = [];
		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$results[ $key ] = $data[ $key ];
			}
		}

		return $results;
	}

	public static function get_values_where_key_contains( $substrs, $data ) {
		$results = [];
		foreach ( $data as $key => $value ) {
			foreach ( $substrs as $substr ) {
				if ( strpos( $key, $substr ) ) {
					$results[ $key ] = $value;
				}
			}
		}

		return $results;
	}

	public static function parse_url( $url, $component = 'host' ) {
		$parts = wp_parse_url( $url );
		return ( is_array( $parts ) && isset( $parts[ $component ] ) ) ? $parts[ $component ] : '';
	}

	/**
	 * Check and return an array key
	 *
	 * @since   2.4.2
	 * @since   2.10.0 Only return `null` if option does not exist.
	 *
	 * @param   array  $array Array with values.
	 * @param   string $key   Name of the key.
	 * @return  mixed         Value of the requested key.
	 */
	public static function get_key( $array, $key ) {
		if ( empty( $array ) || empty( $key ) || ! isset( $array[ $key ] ) ) {
			return null;
		}

		return $array[ $key ];
	}
}
