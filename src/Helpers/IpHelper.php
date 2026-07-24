<?php
/**
 * IP helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * IP address helper.
 */
class IpHelper {

	/**
	 * Return real client IP.
	 *
	 * @return string Client IP.
	 */
	public static function get_client_ip(): string {
		/**
		 * Filters the IP address of the current client.
		 *
		 * This reuses WordPress core’s `pre_comment_user_ip` filter. By default the
		 * value is taken from `REMOTE_ADDR`; use this filter to supply an IP from a
		 * trusted proxy header instead.
		 *
		 * @since 2.6.1
		 *
		 * @param string $client_ip The client IP address. Defaults to REMOTE_ADDR.
		 */
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$client_ip = (string) apply_filters( 'pre_comment_user_ip', wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		return self::sanitize_ip( $client_ip );
	}

	/**
	 * Sanitize an IP string.
	 *
	 * @param string $raw_ip The raw IP.
	 *
	 * @return string The sanitized IP or an empty string.
	 */
	private static function sanitize_ip( string $raw_ip ): string {
		if ( strpos( $raw_ip, ',' ) !== false ) {
			$ips    = explode( ',', $raw_ip );
			$raw_ip = trim( $ips[0] );
		}
		if ( function_exists( 'filter_var' ) ) {
			return (string) filter_var(
				$raw_ip,
				FILTER_VALIDATE_IP
			);
		}

		return (string) preg_replace(
			'/[^0-9a-f:. ]/i',
			'',
			$raw_ip
		);
	}

	/**
	 * Anonymize an IP address.
	 *
	 * @param string $ip Original IP.
	 *
	 * @return  string Anonymous IP.
	 */
	public static function anonymize_ip( string $ip ): string {
		if ( ! preg_match( '/\w+([\.:])\w+/', $ip, $matches ) ) {
			return $ip;
		}
		$ip_start = $matches[0];
		if ( '.' === $matches[1] ) {
			return $ip_start . '.0.0';
		}

		return $ip_start . '::';
	}
}
