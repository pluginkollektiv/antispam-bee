<?php
/**
 * Language Spam Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\LangHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

/**
 * This rule checks for allowed languages in a comment's content.
 */
class LangSpam extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-lang-spam';

	/**
	 * Verify an item.
	 *
	 * Check for allowed languages.
	 *
	 * @param array<string, mixed> $item Item to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$allowed_languages = array_keys( (array) Settings::get_option( static::get_option_name( 'allowed' ), $item['reaction_type'] ) );

		$comment_content = DataHelper::get_values_where_key_contains( [ 'content' ], $item );
		if ( empty( $comment_content ) ) {
			return 0;
		}
		$comment_content = array_shift( $comment_content );
		$comment_text    = wp_strip_all_tags( $comment_content );

		if ( empty( $allowed_languages ) || empty( $comment_text ) ) {
			return 0;
		}

		/**
		 * Filters the detected language. With this filter, other detection methods can skip in and detect the language.
		 *
		 * @param null   $detected_language The detected language.
		 * @param string $comment_text      The text, to detect the language.
		 *
		 * @return null|string The detected language or null.
		 * @since 2.8.2
		 */
		$detected_language = apply_filters( 'antispam_bee_detected_lang', null, $comment_text );
		if ( null !== $detected_language ) {
			return (int) ! in_array( $detected_language, $allowed_languages, true );
		}

		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $comment_text ) ?? '', ' ' );

		if ( function_exists( 'wp_get_word_count_type' ) ) {
			$word_count_type = wp_get_word_count_type();
		} else {
			/*
			 * translators: If your word count is based on single characters (e.g. East Asian characters),
			 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
			 * Do not translate into your own language.
			 */
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			$word_count_type = _x( 'words', 'Word count type. Do not translate!' );
		}

		if ( strpos( $word_count_type, 'characters' ) === 0 && preg_match(
			'/^utf\-?8$/i',
			get_option( 'blog_charset' )
		) ) {
			preg_match_all( '/./u', $text, $words_array );
			$word_count = count( $words_array[0] );
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
			$word_count  = count( $words_array );
		}

		if ( $word_count < 10 ) {
			return 0;
		}

		/**
		 * Filters the language detection API endpoint URL.
		 *
		 * Must resolve to a publicly reachable URL because the request is sent
		 * via wp_safe_remote_post(), which rejects private/internal addresses.
		 * To use a local service for testing, hook antispam_bee_detected_lang
		 * instead and make the HTTP call with wp_remote_post() directly.
		 *
		 * @param string $api_url The language API URL.
		 *
		 * @return string The language API URL.
		 * @since 3.0.0
		 */
		$api_url = apply_filters( 'antispam_bee_lang_api_url', 'https://api.pluginkollektiv.org/language/v1/' );

		$response = wp_safe_remote_post(
			$api_url,
			[ 'body' => (string) wp_json_encode( [ 'body' => $comment_text ] ) ]
		);

		if ( is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return 0;
		}

		$detected_language = wp_remote_retrieve_body( $response );
		if ( ! $detected_language ) {
			return 0;
		}

		$detected_language = json_decode( $detected_language );
		if ( ! $detected_language || ! isset( $detected_language->code ) ) {
			return 0;
		}

		return (int) ! in_array( LangHelper::map( $detected_language->code ), $allowed_languages, true );
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Language', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Allow reactions only in certain language', 'antispam-bee' );
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
				__( 'https://antispambee.pluginkollektiv.org/documentation/#allow-comments-only-in-certain-language', 'antispam-bee' ),
				[ 'https' ]
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with a link to documentation. 2: closing </a> tag. */
			esc_html__( 'Detect and approve only the specified language. Please note the %1$sprivacy notice%2$s for this option.', 'antispam-bee' ),
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
		$languages = [
			'de' => __( 'German', 'antispam-bee' ),
			'en' => __( 'English', 'antispam-bee' ),
			'fr' => __( 'French', 'antispam-bee' ),
			'it' => __( 'Italian', 'antispam-bee' ),
			'es' => __( 'Spanish', 'antispam-bee' ),
		];

		/**
		 * Filter the possible languages for the language spam test.
		 *
		 * @param (array) $languages The languages.
		 *
		 * @return array The list of allowed languages.
		 * @since 2.7.1
		 */
		$languages = (array) apply_filters( 'antispam_bee_get_allowed_translate_languages', $languages );

		return [
			[
				'type'        => 'checkbox-group',
				'options'     => $languages,
				'label'       => __( 'Allowed languages', 'antispam-bee' ),
				'option_name' => 'allowed',
				'sanitize'    => function ( $value ) use ( $languages ) {
					return Sanitize::checkbox_group( $value, $languages );
				},
			],
		];
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return __( 'Language', 'antispam-bee' );
	}
}
