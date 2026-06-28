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
	 * By default, only `REMOTE_ADDR` is evaluated. Use the `pre_comment_user_ip`
	 * filter to supply an IP from a trusted proxy header instead.
	 *
	 * @hook    string  pre_comment_user_ip  The client IP, defaults to REMOTE_ADDR.
	 *
	 * @return string Client IP
	 */
	public static function get_client_ip(): string {
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		return self::sanitize_ip(
			(string) apply_filters( 'pre_comment_user_ip', wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) )
		);
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
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
			'/[^0-9a-f:. ]/si',
			'',
			$raw_ip
		);
	}

	/**
	 * Anonymize the IP addresses
	 *
	 * @param string $ip Original IP.
	 *
	 * @return  string     Anonymous IP.
	 * @since   2.5.1
	 */
	public static function anonymize_ip( string $ip ): string {
		preg_match( '/\w+([\.:])\w+/', $ip, $matches );
		$ip_start = $matches[0];
		if ( '.' === $matches[1] ) {
			return $ip_start . '.0.0';
		}

		return $ip_start . '::';
	}
}
