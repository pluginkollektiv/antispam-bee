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
	 * Return real client IP
	 *
	 * @return string Client IP
	 */
	public static function get_client_ip() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// Sanitization of $ip takes place further down.
		$ip = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED'] );
		}

		$ip = self::sanitize_ip( $ip );
		if ( $ip ) {
			return $ip;
		}

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return self::sanitize_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Sanitize an IP string.
	 *
	 * @param string $raw_ip The raw IP.
	 *
	 * @return string The sanitized IP or an empty string.
	 */
	private static function sanitize_ip( $raw_ip ) {
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
	public static function anonymize_ip( $ip ) {
		preg_match( '/\w+([\.:])\w+/', $ip, $matches );
		$ip_start = $matches[0];
		if ( '.' === $matches[1] ) {
			return $ip_start . '.0.0';
		}

		return $ip_start . '::';
	}
}
