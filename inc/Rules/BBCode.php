<?php

namespace AntispamBee\Rules;

class BBCode implements Verifiable, Controllable {

	use InitRule;

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

	public static function render() {
		return '';
	}

	public static function get_options() {
		return null;
	}
}
