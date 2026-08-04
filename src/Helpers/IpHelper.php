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
	 * below `/48`, so the extra group would identify the visitor without improving
	 * the lookup.
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
		} elseif ( self::is_ipv4_mapped( $packed ) ) {
			// An IPv4-mapped IPv6 address carries an IPv4 address, mask that one.
			$netmask = '::ffff:255.255.255.0';
		} else {
			$netmask = 'ffff:ffff:ffff::';
		}

		$anonymized = inet_ntop( $packed & (string) inet_pton( $netmask ) );

		return false === $anonymized ? '' : $anonymized;
	}

	/**
	 * Check whether an IP address is globally routable.
	 *
	 * Loopback, link-local, private and other reserved addresses have no country,
	 * so there is no point in asking a geolocation service about them. Anything
	 * that is not an IP address at all is not global either.
	 *
	 * @param string $ip The IP address to check.
	 *
	 * @return bool Whether the address is a global one.
	 */
	public static function is_global_ip( string $ip ): bool {
		$packed = inet_pton( $ip );

		if ( false === $packed ) {
			return false;
		}

		/*
		 * `FILTER_FLAG_NO_RES_RANGE` rejects the whole `::ffff:0:0/96` block, which
		 * would discard the global address an IPv4-mapped IPv6 address carries, so
		 * check that IPv4 address instead of the mapped form.
		 */
		if ( self::is_ipv4_mapped( $packed ) ) {
			$ip = (string) inet_ntop( substr( $packed, 12 ) );
		}

		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Check whether a packed address is an IPv4-mapped IPv6 address.
	 *
	 * Such an address carries an IPv4 address in its last four bytes and can show
	 * up in `REMOTE_ADDR` on a dual-stack host.
	 *
	 * @param string $packed The packed address, as returned by `inet_pton()`.
	 *
	 * @return bool Whether the address is an IPv4-mapped one.
	 */
	private static function is_ipv4_mapped( string $packed ): bool {
		return 16 === strlen( $packed )
			&& 0 === strncmp( $packed, str_repeat( "\0", 10 ) . "\xff\xff", 12 );
	}
}
