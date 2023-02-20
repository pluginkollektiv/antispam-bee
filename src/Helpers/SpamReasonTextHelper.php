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

			if ( null === $text ) {
				$slug = PluginUpdate::$spam_reasons_mapping[ $slug ] ?? null;
				if ( $slug ) {
					$text = self::$slug_text_array[ $slug ] ?? null;
				}
			}

			if ( null !== $text ) {
				$texts[] = esc_html( $text );
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
