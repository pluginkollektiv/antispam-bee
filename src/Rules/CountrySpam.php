<?php
/**
 * Country Spam Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\IpHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

/**
 * Checks comments for spam based on the country of the IP address.
 */
class CountrySpam extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-country-spam';

	/**
	 * Verify an item.
	 *
	 * Check whether a reaction originates from an allowed country.
	 *
	 * Handled payload attributes: `ip`, `reaction_type`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		if ( empty( $item['ip'] ) ) {
			return 0;
		}
		$ip = $item['ip'];

		$country_allowed = Settings::get_option( static::get_option_name( 'allowed' ), $item['reaction_type'] ) ?: '';
		$country_denied  = Settings::get_option( static::get_option_name( 'denied' ), $item['reaction_type'] ) ?: '';

		$allowed = preg_split(
			'/[\s,;]+/',
			$country_allowed,
			-1,
			PREG_SPLIT_NO_EMPTY
		) ?: [];
		$denied  = preg_split(
			'/[\s,;]+/',
			$country_denied,
			-1,
			PREG_SPLIT_NO_EMPTY
		) ?: [];

		if ( empty( $allowed ) && empty( $denied ) ) {
			return 0;
		}

		/**
		 * Filter to hook into the `Country_Spam::verify` functionality to implement, for example, a custom IP check.
		 *
		 * @since 2.10.0
		 *
		 * @param null   $is_country_spam The `is_country_spam` result.
		 * @param string $ip              The IP address.
		 * @param array  $allowed         The list of allowed country codes.
		 * @param array  $denied          The list of denied country codes.
		 */
		$is_country_spam = apply_filters( 'antispam_bee_is_country_spam', null, $ip, $allowed, $denied );

		if ( is_bool( $is_country_spam ) ) {
			return (int) $is_country_spam;
		}

		/*
		 * Loopback, link-local and private addresses have no country, which is a
		 * common setup: a local install reports `127.0.0.1` or `::1`, and a site
		 * behind a reverse proxy without a `pre_comment_user_ip` filter sees the
		 * address of the proxy. Asking the service anyway would only tell it that
		 * this site exists.
		 */
		if ( ! IpHelper::is_global_ip( $ip ) ) {
			return 0;
		}

		/**
		 * Filters the IP address the country is looked up for.
		 *
		 * By default only the anonymized address is sent to the service. Return the
		 * original address to trade privacy for a more precise country, mask it
		 * differently, or return an empty string to skip the lookup altogether.
		 *
		 * @since 3.0.0
		 *
		 * @param string $lookup_ip The anonymized IP address.
		 * @param string $ip        The original IP address.
		 */
		$lookup_ip = (string) apply_filters( 'antispam_bee_country_spam_ip', IpHelper::anonymize_ip( $ip ), $ip );

		// Anonymization can fail, and the filter can decline an address on purpose.
		if ( '' === $lookup_ip ) {
			return 0;
		}

		$apikey = trim( self::get_api_key() );

		/*
		 * The service answers anonymous lookups within its free tier, but rejects
		 * an empty `apikey` parameter with `401 Invalid API key`. Only send the key
		 * when there is one, and send it as a header so that it stays out of the
		 * URL, and with it out of proxy and server logs.
		 */
		$args = '' === $apikey ? [] : [ 'headers' => [ 'X-Api-Key' => $apikey ] ];

		$response = wp_safe_remote_get(
			esc_url_raw(
				sprintf(
					'https://www.iplocate.io/api/lookup/%s',
					$lookup_ip
				),
				[ 'https' ]
			),
			$args
		);

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );

		$json = json_decode( $body, true );

		// Check if response is valid json.
		if ( ! is_array( $json ) ) {
			return 0;
		}

		if ( empty( $json['country_code'] ) ) {
			return 0;
		}

		$country = strtoupper( $json['country_code'] );

		if ( strlen( $country ) !== 2 ) {
			return 0;
		}

		/*
		 * Both lists can be configured at once, so neither may short-circuit the
		 * other: a denied country is spam, and once an allow list exists every
		 * country outside it is spam too.
		 */
		if ( ! empty( $denied ) && in_array( $country, $denied, true ) ) {
			return 1;
		}

		if ( ! empty( $allowed ) && ! in_array( $country, $allowed, true ) ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Country Check', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Block or allow comments from specific countries', 'antispam-bee' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string|null The rule description, or null.
	 */
	public static function get_description(): ?string {
		$link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__(
					'https://antispambee.pluginkollektiv.org/documentation/#block-comments-from-specific-countries',
					'antispam-bee'
				),
				[ 'https' ]
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with a link to documentation. 2: closing </a> tag. */
			esc_html__(
				'Filtering the requests depending on country. Please note the %1$sprivacy notice%2$s for this option.',
				'antispam-bee'
			),
			wp_kses_post( $link ),
			'</a>'
		);
	}

	/**
	 * Get the options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array<int, array<string, mixed>> The rule options.
	 */
	public static function get_options(): array {
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
						'href'   => true,
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
						'href'   => true,
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

	/**
	 * Sanitize ISO code strings.
	 *
	 * Reached from a `sanitize` callback, which is handed the posted value as it
	 * is. The field is a textarea, but a request can post an array for it just as
	 * easily, and an array reaching a declared `string` parameter is a `TypeError`
	 * rather than a value this can reject. So anything that is not a string counts
	 * as no countries at all, the same way {@see Sanitize::checkbox()} and
	 * {@see Sanitize::checkbox_group()} discard what they cannot use.
	 *
	 * @param mixed $value Comma-separated list of potential ISO country codes.
	 *
	 * @return string Comma-separated list of sanitized ISO country codes.
	 */
	private static function sanitize_iso_codes_string( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value  = strtoupper( $value );
		$values = explode( ',', $value );
		$values = Sanitize::iso_codes( $values );

		return implode( ',', $values );
	}

	/**
	 * Get the IPLocate API key.
	 *
	 * The `ANTISPAM_BEE_IPLOCATE_API_KEY` constant takes precedence over the filter, so
	 * the key can be deployed with the environment instead of with code. An empty or
	 * undefined constant falls through to the filter, and without either the lookup is
	 * made against IPLocate's free rate limit.
	 *
	 * @since 3.0.0
	 *
	 * @return string The API key, or an empty string when none is configured.
	 */
	private static function get_api_key(): string {
		if ( defined( 'ANTISPAM_BEE_IPLOCATE_API_KEY' ) && ANTISPAM_BEE_IPLOCATE_API_KEY ) {
			return (string) ANTISPAM_BEE_IPLOCATE_API_KEY;
		}

		/**
		 * Filters the IPLocate API key. With this filter, you can add your own IPLocate API key.
		 *
		 * The `ANTISPAM_BEE_IPLOCATE_API_KEY` constant takes precedence over this filter.
		 *
		 * @since 2.10.0
		 *
		 * @param string $apikey The current IPLocate API key. Default is empty string.
		 */
		return (string) apply_filters( 'antispam_bee_country_spam_apikey', '' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return __( 'Country', 'antispam-bee' );
	}
}
