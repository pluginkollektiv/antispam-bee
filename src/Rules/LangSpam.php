<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\LangHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\SpamReason;

class LangSpam extends ControllableBase implements SpamReason {

	protected static $slug = 'asb-lang-spam';

	public static function verify( $item ) {
		$allowed_languages = array_keys( (array) Settings::get_option( static::get_option_name( 'allowed' ), $item['content_type'] ) );

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
		 * @param null $detected_language The detected language.
		 * @param string $comment_text The text, to detect the language.
		 *
		 * @return null|string The detected language or null.
		 * @since 2.8.2
		 */
		$detected_language = apply_filters( 'antispam_bee_detected_lang', null, $comment_text );
		if ( null !== $detected_language ) {
			return ! in_array( $detected_language, $allowed_languages, true );
		}

		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $comment_text ), ' ' );

		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		if ( strpos(
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			_x( 'words', 'Word count type. Do not translate!' ),
			'characters'
			) === 0 && preg_match(
				'/^utf\-?8$/i',
				get_option( 'blog_charset' )
			) ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			preg_match_all( '/./u', $text, $words_array );
			$word_count = 0;
			if ( isset( $words_array[0] ) ) {
				$word_count = count( $words_array[0] );
			}
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $text, - 1, PREG_SPLIT_NO_EMPTY );
			$word_count  = count( $words_array );
		}

		if ( $word_count < 10 ) {
			return 0;
		}

		$response = wp_safe_remote_post(
			'https://api.pluginkollektiv.org/language/v1/',
			array( 'body' => wp_json_encode( [ 'body' => $comment_text ] ) )
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

	public static function get_name() {
		return __( 'Language', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Allow reactions only in certain language', 'antispam-bee' );
	}

	public static function get_description() {
		$link1 = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__( 'https://antispambee.pluginkollektiv.org/documentation/#allow-comments-only-in-certain-language', 'antispam-bee' ),
				'https'
			)
		);

		return sprintf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag. */
			esc_html__( 'Detect and approve only the specified language. Please note the %1$sprivacy notice%2$s for this option.', 'antispam-bee' ),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}

	public static function get_options() {
		$languages = [
			'de' => __( 'German', 'antispam-bee' ),
			'en' => __( 'English', 'antispam-bee' ),
			'fr' => __( 'French', 'antispam-bee' ),
			'it' => __( 'Italian', 'antispam-bee' ),
			'es' => __( 'Spanish', 'antispam-bee' ),
		];

		// Todo: Deprecate old filters
		/**
		 * Filter the possible languages for the language spam test
		 *
		 * @param (array) $languages The languages
		 *
		 * @return (array)
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

	public static function get_reason_text() {
		return __( 'Language', 'antispam-bee' );
	}
}
