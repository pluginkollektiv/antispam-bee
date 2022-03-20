<?php

namespace AntispamBee\Helpers;

class IpHelper {
	/**
	 * Return real client IP
	 *
	 * @return  mixed  $ip  Client IP
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

		$ip = self::_sanitize_ip( $ip );
		if ( $ip ) {
			return $ip;
		}

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
			return self::_sanitize_ip( $ip );
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
	private static function _sanitize_ip( $raw_ip ) {

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
}
