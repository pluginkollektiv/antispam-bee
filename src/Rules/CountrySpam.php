<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\IpHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

/**
 * Checks comments for spam based on the country of the IP address.
 */
class CountrySpam extends ControllableBase implements SpamReason {
	protected static $slug = 'asb-country-spam';

	public static function verify( $item ) {
		if ( ! isset( $item['comment_author_IP'] ) || empty( $item['comment_author_IP'] ) ) {
			return 0;
		}
		$ip = $item['comment_author_IP'];

		// Todo: Migrate ab_country_allowed, ab_country_denied
		$country_allowed = Settings::get_option( static::get_option_name( 'allowed' ), $item['reaction_type'] );
		$country_allowed = $country_allowed ? $country_allowed : '';
		$country_denied  = Settings::get_option( static::get_option_name( 'denied' ), $item['reaction_type'] );
		$country_denied  = $country_denied ? $country_denied : '';

		$allowed = preg_split(
			'/[\s,;]+/',
			$country_allowed,
			- 1,
			PREG_SPLIT_NO_EMPTY
		);
		$denied  = preg_split(
			'/[\s,;]+/',
			$country_denied,
			- 1,
			PREG_SPLIT_NO_EMPTY
		);

		if ( empty( $allowed ) && empty( $denied ) ) {
			return 0;
		}

		/**
		 * Filter to hook into the `Country_Spam::verify` functionality, to implement for example a custom IP check.
		 *
		 * @param null $is_country_spam The `is_country_spam` result.
		 * @param string $ip The IP address.
		 * @param array $allowed The list of allowed country codes.
		 * @param array $denied The list of denied country codes.
		 *
		 * @return null|boolean The `is_country_spam` result or null.
		 * @since 2.10.0
		 */
		$is_country_spam = apply_filters( 'antispam_bee_is_country_spam', null, $ip, $allowed, $denied );

		if ( is_bool( $is_country_spam ) ) {
			return (int) $is_country_spam;
		}

		/**
		 * Filters the IPLocate API key. With this filter, you can add your own IPLocate API key.
		 *
		 * @param string  The current IPLocate API key. Default is `null`.
		 *
		 * @return string The changed IPLocate API key or null.
		 * @since 2.10.0
		 */
		$apikey = apply_filters( 'antispam_bee_country_spam_apikey', '' );

		$response = wp_safe_remote_get(
			esc_url_raw(
				sprintf(
					'https://www.iplocate.io/api/lookup/%s?apikey=%s',
					IpHelper::anonymize_ip( $ip ),
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
				__(
					'https://antispambee.pluginkollektiv.org/documentation/#block-comments-from-specific-countries',
					'antispam-bee'
				),
				'https'
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag. */
			esc_html__(
				'Filtering the requests depending on country. Please note the %1$sprivacy notice%2$s for this option.',
				'antispam-bee'
			),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}

	public static function get_options() {
		$iso_codes_link = 'https://www.iso.org/obp/ui/#search/code/';
		return [
			[
				'type'        => 'textarea',
				'label'       => sprintf( /* translators: 1=opening link tag to ISO codes list, 2=closing link tag. */
					__( 'Denied %1$sISO country codes%2$s for this option.', 'antispam-bee' ),
					"<a href='{$iso_codes_link}' target='_blank'>",
					'</a>'
				),
				'label_kses'  => [
					'a' => [
						'href' => true,
						'target' => true,
					],
				],
				'placeholder' => __( 'e.g. BF, SG, YE', 'antispam-bee' ),
				'option_name' => 'denied',
				'sanitize'    => function ( $value ) {
					return self::sanitize_iso_codes_string( $value );
				},
			],
			[
				'type'        => 'textarea',
				'label'       => sprintf( /* translators: 1=opening link tag to ISO codes list, 2=closing link tag. */
					__( 'Allowed %1$sISO country codes%2$s for this option.', 'antispam-bee' ),
					"<a href='{$iso_codes_link}' target='_blank'>",
					'</a>'
				),
				'label_kses'  => [
					'a' => [
						'href' => true,
						'target' => true,
					],
				],
				'placeholder' => __( 'e.g. BF, SG, YE', 'antispam-bee' ),
				'option_name' => 'allowed',
				'sanitize'    => function ( $value ) {
					return self::sanitize_iso_codes_string( $value );
				},
			],
		];
	}

	private static function sanitize_iso_codes_string( $value ) {
		$value  = strtoupper( $value );
		$values = explode( ',', $value );
		$values = Sanitize::iso_codes( $values );

		return implode( ',', $values );
	}

	public static function get_reason_text() {
		return __( 'Country', 'antispam-bee' );
	}
}
