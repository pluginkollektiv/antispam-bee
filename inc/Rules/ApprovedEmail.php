<?php

namespace AntispamBee\Rules;

class ApprovedEmail implements Verifiable, Controllable {

	use InitRule;

	public static function verify( $data ) {
		$email = Data_Helper::get_values_where_key_contains( 'email', $data );
		if ( empty( $email ) ) {
			return 0;
		}

		$email = $email[0];

		$approved_comments_count = get_comments( [
			'status' => 'approved',
			'count' => true,
			'author_email' => $email,
		] );

		if ( 0 === $approved_comments_count ) {
			return 1;
		}

		return - 1;
	}

	public static function get_name() {
		return __( 'Approved Email', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Trust approved commenters', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'No review of already commented users', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-approved-email';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return null;
	}
}