<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

class CountrySpam implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		if ( ! isset( $data['comment_author_IP'] ) || empty( $data['comment_author_IP'] ) ) {
			return 0;
		}
		$ip = $data['comment_author_IP'];

		$options = self::get_options();

		$allowed = preg_split(
			'/[\s,;]+/',
			$options['country_allowed'],
			- 1,
			PREG_SPLIT_NO_EMPTY
		);
		$denied  = preg_split(
			'/[\s,;]+/',
			$options['country_denied'],
			- 1,
			PREG_SPLIT_NO_EMPTY
		);

		if ( empty( $allowed ) && empty( $denied ) ) {
			return 0;
		}

		/**
		 * Filter to hook into the `Country_Spam::verify` functionality, to implement for example a custom IP check.
		 *
		 * @param null   $is_country_spam The `is_country_spam` result.
		 * @param string $ip              The IP address.
		 * @param array  $allowed         The list of allowed country codes.
		 * @param array  $denied          The list of denied country codes.
		 *
		 * @return null|boolean The `is_country_spam` result or null.
		 * @since 2.10.0
		 *
		 */
		$is_country_spam = apply_filters( 'antispam_bee_is_country_spam', null, $ip, $allowed, $denied );

		if ( is_bool( $is_country_spam ) ) {
			return $is_country_spam;
		}

		/**
		 * Filters the IPLocate API key. With this filter, you can add your own IPLocate API key.
		 *
		 * @param string  The current IPLocate API key. Default is `null`.
		 *
		 * @return string The changed IPLocate API key or null.
		 * @since 2.10.0
		 *
		 */
		$apikey = apply_filters( 'antispam_bee_country_spam_apikey', '' );

		$response = wp_safe_remote_get(
			esc_url_raw(
				sprintf(
					'https://www.iplocate.io/api/lookup/%s?apikey=%s',
					self::_anonymize_ip( $ip ),
					$apikey
				),
				'https'
			)
		);

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return 0;
		}

		$body = (string) wp_remote_retrieve_body( $response );

		$json = json_decode( $body, true );

		// Check if response is valid json.
		if ( ! is_array( $json ) ) {
			return 0;
		}

		if ( empty( $json['country_code'] ) ) {
			return 0;
		}

		$country = strtoupper( $json['country_code'] );

		if ( empty( $country ) || strlen( $country ) !== 2 ) {
			return 0;
		}

		if ( ! empty( $denied ) ) {
			return in_array( $country, $denied, true ) ? 1 : 0;
		}

		return in_array( $country, $allowed, true ) ? 0 : 1;
	}

	public static function get_name() {
		return __( 'Country Check', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Block or allow comments from specific countries', 'antispam-bee' );
	}

	public static function get_description() {
		$link1 = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__( 'https://antispambee.pluginkollektiv.org/documentation/#block-comments-from-specific-countries',
					'antispam-bee' ),
				'https'
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag. */
			esc_html__( 'Filtering the requests depending on country. Please note the %1$sprivacy notice%2$s for this option.',
				'antispam-bee' ),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}

	public static function get_weight() {
		return 1;
	}

	public static function get_slug() {
		return 'asb-country-spam';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function is_active( $type ) {
		return false;
	}
}
