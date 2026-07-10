<?php
/**
 * Empty Data Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Checks for empty data.
 */
class EmptyData extends Base implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-empty';

	/**
	 * Verify an item.
	 *
	 * Check for empty content or author.
	 *
	 * Handled payload attributes: `body`, `ip`, `email`, `author`, `url`,
	 * `reaction_type`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$allow_empty_reaction = apply_filters( 'allow_empty_comment', false, $item );
		$content              = $item['body'] ?? '';
		if ( ! $allow_empty_reaction && empty( $content ) ) {
			return 999;
		}

		if ( empty( $item['ip'] ) ) {
			return 999;
		}

		if ( ContentTypeHelper::COMMENT_TYPE === $item['reaction_type'] ) {
			if ( get_option( 'require_name_email' ) && ( empty( $item['email'] ) || empty( $item['author'] ) ) ) {
				return 999;
			}
		}

		if ( ContentTypeHelper::LINKBACK_TYPE === $item['reaction_type'] ) {
			$url = $item['url'] ?? '';
			if ( empty( $url ) ) {
				return 999;
			}
		}

		return 0;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return _x( 'Empty Data', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'Empty Data', 'spam-reason-text', 'antispam-bee' );
	}
}
