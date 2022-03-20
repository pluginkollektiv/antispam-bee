<?php

namespace AntispamBee\Rules;

class ShortestTime implements Verifiable, Controllable {

	use InitRule;

	public static function verify( $data ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can Post.
		$init_time = (int) self::get_key( $_POST, 'ab_init_time' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( 0 === $init_time ) {
			return false;
		}

		if ( time() - $init_time < apply_filters( 'ab_action_time_limit', 5 ) ) {
			return true;
		}

		return false;
	}

	public static function get_name() {
		return __( 'Comment time', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-shortest-time';
	}

	public static function is_final() {
		return false;
	}

	public static function get_label() {
		return __( 'Consider the comment time', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Not recommended when using page caching', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}
}
