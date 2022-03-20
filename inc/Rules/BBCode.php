<?php

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

class BBCode implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		foreach ( $data as $value ) {
			if ( true === preg_match( '/\[url[=\]].*\[\/url\]/is', $value ) ) {
				return 1;
			}
		}
		return -1;
	}

	public static function get_name() {
		return __( 'BBCode', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'BBCode links are spam', 'antispam-bee' );
	}

	public static function get_description() {
		__( 'Review the comment contents for BBCode links', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-bbcode';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ 'comment', 'trackback' ];
	}

	public static function is_active() {
		return false;
	}
}
