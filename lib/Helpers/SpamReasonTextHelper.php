<?php

namespace AntispamBee\Helpers;

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
	}

	/**
	 * Gets an array with rule slugs as keys and reason texts as value.
	 *
	 * @return array
	 */
	public static function get_slug_text_array() {
		if ( self::$slug_text_array ) {
			return self::$slug_text_array;
		}

		throw new \Exception( 'Slug reason text array not populated. Try later than the `init` hook with priority `10`!' );
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
			if ( isset( self::$slug_text_array[ $slug ] ) ) {
				$texts[] = esc_html( self::$slug_text_array[ $slug ] );
				continue;
			}

			$texts[] = esc_html_x(
			/* translators: s=slug of unknown spam reason */
				sprintf( 'Unknown rule: %s', $slug ),
				'spam-reason-unknown-text',
				'antispam-bee'
			);
		}

		return $texts;
	}
}
