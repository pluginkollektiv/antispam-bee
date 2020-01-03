<?php
/**
 * Sometimes, we have to work with IPs. This helper provides some methods, which lift this work, like
 * anonymization or IP detection.
 *
 * @package Antispam Bee Helper
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Helper;

/**
 * Class ClientIP
 *
 * @package Pluginkollektiv\AntispamBee\Helper
 */
class IP {


	/**
	 * Anonymize an IP.
	 *
	 * @param string $ip The IP to anonymize.
	 *
	 * @return string
	 */
	public function anonymize_ip( string $ip ) {

		if ( $this->is_ipv4( $ip ) ) {
			return $this->cut_ip( $ip ) . '.0';
		}

		return $this->cut_ip( $ip, false ) . ':0:0:0:0:0:0:0';
	}

	/**
	 * Cuts an IP address.
	 *
	 * @param string $ip      The address to cut.
	 * @param bool   $cut_end Where to cut the IP.
	 *
	 * @return string
	 */
	private function cut_ip( string $ip, bool $cut_end = true ) {

		$separator = ( $this->is_ipv4( $ip ) ? '.' : ':' );

		return str_replace(
			( $cut_end ? strrchr( $ip, $separator ) : strstr( $ip, $separator ) ),
			'',
			$ip
		);
	}

	/**
	 * Detects if an IP is IPv4.
	 *
	 * @param string $ip The IP to check.
	 *
	 * @return bool
	 */
	private function is_ipv4( string $ip ) {
		if ( function_exists( 'filter_var' ) ) {
			return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
		}
		return (bool) preg_match( '/^\d{1,3}(\.\d{1,3}){3,3}$/', $ip );

	}

	/**
	 * Detects the IP of the client.
	 *
	 * @ToDo: Possible regression, see https://github.com/pluginkollektiv/antispam-bee/issues/286
	 * @param  string $old_ip Will be returned as a fallback, if no IP has been found.
	 * @return string
	 */
	public function detect_client_ip( string $old_ip = '' ) {

     // phpcs:disable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
		// Sanitization of $ip takes place further down.
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ); // Input var okay.
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ); // Input var okay.
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ); // Input var okay.
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ); // Input var okay.
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED'] ); // Input var okay.
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // Input var okay.
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] ); // Input var okay.
		} else {
			return $old_ip;
		}
        // phpcs:enable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized

		if ( strpos( $ip, ',' ) !== false ) {
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );
		}

		if ( function_exists( 'filter_var' ) ) {
			return filter_var(
				$ip,
				FILTER_VALIDATE_IP
			);
		}

		return preg_replace(
			'/[^0-9a-f:\., ]/si',
			'',
			$ip
		);
	}
}
