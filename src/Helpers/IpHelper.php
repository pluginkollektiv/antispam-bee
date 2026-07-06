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
	 * Anonymize an IP address.
	 *
	 * Only the network portion is kept, the host portion is zeroed out. For
	 * IPv4 the first three octets are retained (a `/24` network), for IPv6 the
	 * first three groups are retained (a `/48` network). This keeps enough of
	 * the address for reliable country-level geolocation while removing the host.
	 *
	 * WordPress core's {@see wp_privacy_anonymize_ip()} is used when available;
	 * it applies the same masks and additionally handles ports, brackets and
	 * zone identifiers. The bundled masking is only a fallback for environments
	 * where that function does not exist.
	 *
	 * @param string $ip Original IP.
	 *
	 * @return  string     Anonymous IP.
	 * @since   2.5.1
	 */
	public static function anonymize_ip( string $ip ): string {
		if ( function_exists( 'wp_privacy_anonymize_ip' ) ) {
			return wp_privacy_anonymize_ip( $ip );
		}

		return self::mask_ip( $ip );
	}

	/**
	 * Fallback anonymization used when {@see wp_privacy_anonymize_ip()} is
	 * unavailable.
	 *
	 * Keeps the first three octets of an IPv4 address (a `/24` network) and the
	 * first three groups of an IPv6 address (a `/48` network). Invalid input is
	 * returned unchanged.
	 *
	 * @param string $ip Original IP.
	 *
	 * @return string Anonymous IP.
	 */
	private static function mask_ip( string $ip ): string {
		$packed = filter_var( $ip, FILTER_VALIDATE_IP ) ? inet_pton( $ip ) : false;

		if ( false === $packed ) {
			return $ip;
		}

		if ( 4 === strlen( $packed ) ) {
			// IPv4: keep the first three octets (a /24 network).
			$mask = (string) inet_pton( '255.255.255.0' );
		} else {
			// IPv6: keep the first three groups (a /48 network).
			$mask = (string) inet_pton( 'ffff:ffff:ffff::' );
		}

		return (string) inet_ntop( $packed & $mask );
	}
}
