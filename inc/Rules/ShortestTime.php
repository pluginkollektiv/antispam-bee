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
		// TODO: Implement get_name() method.
	}

	public static function get_weight() {
		// TODO: Implement get_weight() method.
	}

	public static function get_slug() {
		// TODO: Implement get_slug() method.
	}

	public static function is_final() {
		// TODO: Implement is_final() method.
	}

	public static function get_label() {
		__( 'Consider the comment time', 'antispam-bee' );
	}

	public static function get_description() {
		// TODO: Implement get_description() method.
	}

	public static function render() {
		// TODO: Implement render() method.
	}
}