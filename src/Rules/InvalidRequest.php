<?php

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks if request is valid.
 */
class InvalidRequest extends Base implements SpamReason {
	protected static $slug = 'asb-invalid-request';

	public static function verify( $item ) {
		if ( isset( $item[ 'ab_spam__invalid_request'] ) && $item[ 'ab_spam__invalid_request'] ) {
			return 999;
		}

		return 0;
	}

	public static function get_name() {
		return _x( 'Invalid Request', 'spam-reason-form-name', 'antispam-bee' );
	}

	public static function get_reason_text() {
		return _x( 'Invalid Request', 'spam-reason-text', 'antispam-bee' );
	}
}
