<?php
/**
 * Invalid Request Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks if the request is valid.
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
	 * Consumes no payload attributes; reads the request (`$_POST`) directly.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_POST['ab_spam__invalid_request'] ) && $_POST['ab_spam__invalid_request'] ) {
			return 999;
		}

		return 0;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return _x( 'Invalid Request', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'Invalid Request', 'spam-reason-text', 'antispam-bee' );
	}
}
