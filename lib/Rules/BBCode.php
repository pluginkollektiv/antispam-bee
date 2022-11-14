<?php

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks comment content for BBCode URLs.
 */
class BBCode extends ControllableBase implements SpamReason {
	protected static $slug = 'asb-bbcode';

	public static function verify( $item ) {
		foreach ( $item as $value ) {
			if ( true === (bool) preg_match( '/\[url[=\]].*\[\/url\]/is', $value ) ) {
				return 1;
			}
		}

		return 0;
	}

	public static function get_name() {
		return _x( 'BBCode', 'spam-reason-form-name', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'BBCode links are spam', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Review the comment contents for BBCode links', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'BBCode', 'spam-reason-text', 'antispam-bee' );
	}
}
