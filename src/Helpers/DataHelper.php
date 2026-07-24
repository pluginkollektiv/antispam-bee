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
