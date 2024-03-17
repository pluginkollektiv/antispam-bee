<?php
/**
 * Invalid Request Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks if request is valid.
 */
class InvalidRequest extends Base implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-invalid-request';

	/**
	 * Verify an item.
	 *
	 * Check for invalid request content in POST data.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( $item ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_POST['ab_spam__invalid_request'] ) && $_POST['ab_spam__invalid_request'] ) {
			return 999;
		}

		return 0;
	}

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return _x( 'Invalid Request', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text() {
		return _x( 'Invalid Request', 'spam-reason-text', 'antispam-bee' );
	}
}
