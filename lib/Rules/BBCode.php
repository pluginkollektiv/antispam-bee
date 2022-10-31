<?php

namespace AntispamBee\Rules;

/**
 * Checks comment content for BBCode URLs.
 */
class BBCode extends ControllableBase {
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
		return __( 'BBCode', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'BBCode links are spam', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Review the comment contents for BBCode links', 'antispam-bee' );
	}
}
