<?php
/**
 * Data helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Data helper.
 */
class DataHelper {

	/**
	 * Get values by keys.
	 *
	 * @param array $keys A list of keys.
	 * @param array $data The data to filter.
	 *
	 * @return array Data elements with matching keys.
	 */
	public static function get_values_by_keys( array $keys, array $data ): array {
		$results = [];
		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$results[ $key ] = $data[ $key ];
			}
		}

		return $results;
	}

	/**
	 * Get values with a key containing given values.
	 *
	 * @param string[] $substrs The key substrings to filter.
	 * @param array    $data    The data to filter.
	 *
	 * @return array Data elements with matching keys.
	 */
	public static function get_values_where_key_contains( array $substrs, array $data ): array {
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

	/**
	 * Parse URL wrapper.
	 *
	 * @param string $url       URL to parse.
	 * @param string $component URL component (default: "host").
	 *
	 * @return string The URL component.
	 */
	public static function parse_url( string $url, string $component = 'host' ): string {
		$parts = wp_parse_url( $url );

		return ( is_array( $parts ) && isset( $parts[ $component ] ) ) ? $parts[ $component ] : '';
	}
}
