<?php

namespace AntispamBee\Rules;

class LangSpam implements Verifiable, Controllable {

	use InitRule;

	public static function verify( $data ) {
		$comment_content = $data;

		$allowed_lang = (array) Antispam_Bee::get_option( 'translate_lang' );

		$comment_text = wp_strip_all_tags( $comment_content );

		if ( empty( $allowed_lang ) || empty( $comment_text ) ) {
			return false;
		}

		/**
		 * Filters the detected language. With this filter, other detection methods can skip in and detect the language.
		 *
		 * @param  null  $detected_lang  The detected language.
		 * @param  string  $comment_text  The text, to detect the language.
		 *
		 * @return null|string The detected language or null.
		 * @since 2.8.2
		 *
		 */
		$detected_lang = apply_filters( 'antispam_bee_detected_lang', null, $comment_text );
		if ( null !== $detected_lang ) {
			return ! in_array( $detected_lang, $allowed_lang, true );
		}

		$word_count = 0;
		$text       = trim( preg_replace( "/[\n\r\t ]+/", ' ', $comment_text ), ' ' );

		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		if ( strpos( _x( 'words', 'Word count type. Do not translate!' ),
				'characters' ) === 0 && preg_match( '/^utf\-?8$/i',
				get_option( 'blog_charset' ) ) ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			preg_match_all( '/./u', $text, $words_array );
			if ( isset( $words_array[0] ) ) {
				$word_count = count( $words_array[0] );
			}
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $text, - 1, PREG_SPLIT_NO_EMPTY );
			$word_count  = count( $words_array );
		}

		if ( $word_count < 10 ) {
			return false;
		}

		$response = wp_safe_remote_post(
			'https://api.pluginkollektiv.org/language/v1/',
			array( 'body' => wp_json_encode( array( 'body' => $comment_text ) ) )
		);

		if ( is_wp_error( $response )
		     || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$detected_lang = wp_remote_retrieve_body( $response );
		if ( ! $detected_lang ) {
			return false;
		}

		$detected_lang = json_decode( $detected_lang );
		if ( ! $detected_lang || ! isset( $detected_lang->code ) ) {
			return false;
		}

		return ! in_array( Lang_Code_Helper::map ( $detected_lang->code ), $allowed_lang, true );
	}

	public static function get_name() {
		return __( 'Comment Language', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1;
	}

	public static function get_slug() {
		return 'asb-lang-spam';
	}

	public static function is_final() {
		return false;
	}

	public static function get_label() {
		__( 'Allow comments only in certain language', 'antispam-bee' );
	}

	public static function get_description() {
		$link1 = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">',
			esc_url(
				__( 'https://antispambee.pluginkollektiv.org/documentation/#allow-comments-only-in-certain-language', 'antispam-bee' ),
				'https'
			)
		);

		printf(
		/* translators: 1: opening <a> tag with link to documentation. 2: closing </a> tag. */
			esc_html__( 'Detect and approve only the specified language. Please note the %1$sprivacy notice%2$s for this option.', 'antispam-bee' ),
			wp_kses_post( $link1 ),
			'</a>'
		);
	}

	public static function get_options() {
		null;
	}
}