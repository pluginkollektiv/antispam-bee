<?php

namespace AntispamBee\Helpers;

use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Handlers\Rules;

class SpamReasonTextHelper {
	protected static $slug_text_array;

	public static function init() {
		add_action( 'init', [ __CLASS__, 'populate' ] );
	}

	public static function populate() {
		$rules                 = Rules::get_spam_rules();
		self::$slug_text_array = [];
		foreach ( $rules as $rule ) {
			self::$slug_text_array[ $rule::get_slug() ] = $rule::get_reason_text();
		}

		/**
		 * Allow to add more reasons.
		 *
		 * @param $additional_reasons array Array of additional reasons. Key is the reason slug, value the label users see in the backend.
		 */
		$additional_reasons    = (array) apply_filters( 'antispam_bee_additional_spam_reasons', [] );
		self::$slug_text_array = array_merge( $additional_reasons, self::$slug_text_array );
	}

	/**
	 * Gets the spam reason texts by an array of slugs
	 *
	 * @param array $slugs
	 *
	 * @return array
	 */
	public static function get_texts_by_slugs( array $slugs ) {
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
