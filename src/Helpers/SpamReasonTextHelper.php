<?php
/**
 * Spam Reason Text helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use AntispamBee\Handlers\Rules;

/**
 * Spam Reason Text helper.
 */
class SpamReasonTextHelper {

	/**
	 * List of spam reasons by rule slug.
	 *
	 * @var array<string, string>
	 */
	protected static $slug_text_array;

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'populate' ] );
	}

	/**
	 * Populate spam reasons.
	 *
	 * @return void
	 */
	public static function populate(): void {
		$rules                 = Rules::get_spam_reason_rules();
		self::$slug_text_array = [];
		foreach ( $rules as $rule ) {
			self::$slug_text_array[ $rule::get_slug() ] = $rule::get_reason_text();
		}

		/**
		 * Filters the additional spam reasons.
		 *
		 * Use this filter to register spam reasons for custom rules so their label
		 * is displayed in the backend. The array key is the reason slug, the value
		 * the label users see in the backend.
		 *
		 * @param array $additional_reasons A map of reason slugs to backend labels.
		 *
		 * @return array The map of reason slugs to backend labels.
		 * @since 3.0.0
		 */
		$additional_reasons    = (array) apply_filters( 'antispam_bee_additional_spam_reasons', [] );
		self::$slug_text_array = array_merge( $additional_reasons, self::$slug_text_array );
	}

	/**
	 * Get the spam reason texts by an array of slugs.
	 *
	 * @param string[] $slugs A list of rule slugs.
	 *
	 * @return string[] Texts for given slugs.
	 */
	public static function get_texts_by_slugs( array $slugs ): array {
		$texts = [];
		foreach ( $slugs as $slug ) {
			$text = self::$slug_text_array[ $slug ] ?? null;

			if ( null !== $text ) {
				$texts[] = esc_html( $text );
				continue;
			}

			$legacy_rules = [
				'server' => esc_html_x( 'Fake IP', 'legacy spam reason label', 'antispam-bee' ),
			];

			if ( array_key_exists( $slug, $legacy_rules ) ) {
				$texts[] = sprintf(
					/* translators: s=slug of unknown spam reason */
					esc_html_x( 'Legacy rule: %s', 'spam-reason-legacy-text', 'antispam-bee' ),
					$legacy_rules[ $slug ]
				);
				continue;
			}
			$texts[] = sprintf(
				/* translators: s=slug of unknown spam reason */
				esc_html_x( 'Unknown rule: %s', 'spam-reason-unknown-text', 'antispam-bee' ),
				$slug
			);
		}

		return $texts;
	}
}
