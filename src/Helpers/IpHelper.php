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
	 * Only the network portion is kept, the host portion is zeroed out: the
	 * first three octets of an IPv4 address (a `/24` network) and the first three
	 * groups of an IPv6 address (a `/48` network). Both are coarse enough to drop
	 * the host and fine enough for a reliable country lookup. An IPv4-mapped IPv6
	 * address is masked like the IPv4 address it carries.
	 *
	 * The IPv4 mask is the one WordPress core applies in
	 * `wp_privacy_anonymize_ip()`. For IPv6 core keeps a `/64`, one step finer than
	 * this. A `/64` is a single subscriber LAN, while country data is never keyed
	 * below `/48`: across 1388 addresses taken from real comments, no announced BGP
	 * prefix was longer than `/48`, and the country IPLocate reports was identical
	 * at `/64`, `/56` and `/48` for every one of them. The first mask that changes
	 * the answer is `/40`. See #761 for the measurements.
	 *
	 * Addresses that cannot be parsed result in an empty string.
	 *
	 * @param string $ip Original IP.
	 *
	 * @return  string Anonymous IP.
	 */
	public static function anonymize_ip( string $ip ): string {
		$packed = inet_pton( $ip );

		if ( false === $packed ) {
			return '';
		}

		// IPv4 addresses pack into four bytes, IPv6 addresses into sixteen.
		if ( 4 === strlen( $packed ) ) {
			$netmask = '255.255.255.0';
		} elseif ( 0 === strncmp( $packed, str_repeat( "\0", 10 ) . "\xff\xff", 12 ) ) {
			// An IPv4-mapped IPv6 address carries an IPv4 address, mask that one.
			$netmask = '::ffff:255.255.255.0';
		} else {
			$netmask = 'ffff:ffff:ffff::';
		}

		$anonymized = inet_ntop( $packed & (string) inet_pton( $netmask ) );

		return false === $anonymized ? '' : $anonymized;
	}
}
