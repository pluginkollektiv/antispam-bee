<?php
/**
 * BB Code Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Interfaces\SpamReason;

/**
 * Checks comment content for BBCode URLs.
 */
class BBCode extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-bbcode';

	/**
	 * Verify an item.
	 *
	 * Check whether any content part contains BBCode links.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		foreach ( $item as $value ) {
			if ( true === (bool) preg_match( '/\[url[=\]].*\[\/url\]/is', $value ) ) {
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return _x( 'BBCode', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get rule label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'BBCode links are spam', 'antispam-bee' );
	}

	/**
	 * Get rule description.
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return __( 'Review the comment contents for BBCode links', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string {
		return _x( 'BBCode', 'spam-reason-text', 'antispam-bee' );
	}
}
