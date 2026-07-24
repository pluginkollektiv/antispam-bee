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
	 * Get the values by keys.
	 *
	 * @param array<array-key>        $keys A list of keys.
	 * @param array<array-key, mixed> $data The data to filter.
	 *
	 * @return array<array-key, mixed> Data elements with matching keys.
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
	 * Get the values with a key containing given values.
	 *
	 * @param string[]                $substrs The key substrings to filter.
	 * @param array<array-key, mixed> $data    The data to filter.
	 *
	 * @return array<array-key, mixed> Data elements with matching keys.
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

		return ( is_array( $parts ) && isset( $parts[ $component ] ) ) ? (string) $parts[ $component ] : '';
	}
}
