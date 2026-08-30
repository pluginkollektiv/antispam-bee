<?php
/**
 * Language Spam Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Admin\Fields\FieldBuilder;
use AntispamBee\Admin\Fields\FieldOptions;
use AntispamBee\Helpers\LangHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use AntispamBee\Helpers\TextHelper;
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
	 * Handled payload attributes: `body`, `reaction_type`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$allowed_languages = array_keys( (array) Settings::get_option( static::get_option_name( 'allowed' ), $item['reaction_type'] ) );

		$comment_content = $item['body'] ?? '';
		if ( empty( $comment_content ) ) {
			return 0;
		}
		$comment_text = wp_strip_all_tags( $comment_content );

		if ( empty( $allowed_languages ) || empty( $comment_text ) ) {
			return 0;
		}

		/**
		 * Filters the detected language. With this filter, other detection methods can skip in and detect the language.
		 *
		 * @since 2.8.2
		 *
		 * @param null   $detected_language The detected language.
		 * @param string $comment_text      The text, to detect the language.
		 */
		$detected_language = apply_filters( 'antispam_bee_detected_lang', null, $comment_text );
		if ( null !== $detected_language ) {
			return (int) ! in_array( $detected_language, $allowed_languages, true );
		}

		if ( ! self::has_enough_text_for_detection( $comment_text ) ) {
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
		 * @since 3.0.0
		 *
		 * @param string $api_url The language API URL.
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
		if ( ! $detected_language || ! isset( $detected_language->code ) || ! is_string( $detected_language->code ) ) {
			return 0;
		}

		/*
		 * The service returns the ISO 639-3 code "und" (undetermined) if it could not
		 * identify the language. A language we do not know is no reason to assume spam.
		 */
		if ( '' === $detected_language->code || 'und' === $detected_language->code ) {
			return 0;
		}

		return (int) ! in_array( LangHelper::map( $detected_language->code ), $allowed_languages, true );
	}

	/**
	 * Check whether a text contains enough content to detect its language.
	 *
	 * The detection service needs a certain amount of text, because it compares
	 * character sequences and is unreliable for short texts.
	 *
	 * Languages that delimit their words with spaces are measured in words. Scripts
	 * that do not use spaces to delimit words, for example the Chinese, Japanese,
	 * Korean or Thai script, are measured in characters instead, because such a text
	 * would otherwise be counted as a single, far too short word.
	 *
	 * @param string $text The text to check.
	 *
	 * @return bool Whether the text contains enough content.
	 */
	private static function has_enough_text_for_detection( string $text ): bool {
		$text = TextHelper::normalize_whitespace( $text );
		if ( '' === $text ) {
			return false;
		}

		$spaceless_script_letters = TextHelper::count_spaceless_script_letters( $text );
		if ( $spaceless_script_letters > 0 ) {
			/**
			 * Filters the minimum number of characters needed to detect the language of a
			 * text written in a script that does not use spaces to delimit words.
			 *
			 * @since 3.0.0
			 *
			 * @param int $min_characters The minimum number of characters.
			 */
			$min_characters = (int) apply_filters( 'antispam_bee_lang_min_characters', 10 );

			/*
			 * Such a script has to account for at least half of all letters. Otherwise, the
			 * service would detect the language of the dominant, space delimited part of the
			 * text, for which those few characters are just noise.
			 */
			if ( TextHelper::count_characters( $text ) >= $min_characters
				&& ( $spaceless_script_letters * 2 ) >= TextHelper::count_letters( $text ) ) {
				return true;
			}
		}

		/**
		 * Filters the minimum number of words needed to detect the language of a text.
		 *
		 * @since 3.0.0
		 *
		 * @param int $min_words The minimum number of words.
		 */
		$min_words = (int) apply_filters( 'antispam_bee_lang_min_words', 10 );

		return TextHelper::count_words( $text ) >= $min_words;
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
	 * Get the rules options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array<int, FieldOptions> The rules options.
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
		 * @since      2.7.1
		 * @deprecated 3.0.0 Use `antispam_bee_allowed_languages` instead.
		 *
		 * @param array<string, string> $languages The languages.
		 */
		$languages = apply_filters_deprecated(
			'antispam_bee_get_allowed_translate_languages',
			[ $languages ],
			'3.0.0',
			'antispam_bee_allowed_languages'
		);

		/**
		 * Filter the possible languages for the language spam test.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, string> $languages The languages.
		 */
		$languages = (array) apply_filters( 'antispam_bee_allowed_languages', $languages );

		return [
			FieldBuilder::checkbox_group()
				->choices( $languages )
				->label( __( 'Allowed languages', 'antispam-bee' ) )
				->option_name( 'allowed' )
				->sanitize(
					function ( $value ) use ( $languages ) {
						return Sanitize::checkbox_group( $value, $languages );
					}
				),
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
