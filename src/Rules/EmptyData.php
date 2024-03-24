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
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$allow_empty_reaction = apply_filters( 'allow_empty_comment', false, $item );
		$content              = $item['comment_content'] ?? '';
		if ( ! $allow_empty_reaction && empty( $content ) ) {
			return 999;
		}

		if ( empty( $item['comment_author_IP'] ) ) {
			return 999;
		}

		if ( ContentTypeHelper::COMMENT_TYPE === $item['reaction_type'] ) {
			if ( get_option( 'require_name_email' ) && ( empty( $item['comment_author_email'] ) || empty( $item['comment_author'] ) ) ) {
				return 999;
			}
		}

		if ( ContentTypeHelper::LINKBACK_TYPE === $item['reaction_type'] ) {
			$url = $item['comment_author_url'] ?? '';
			if ( empty( $url ) ) {
				return 999;
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
		return _x( 'Empty Data', 'spam-reason-form-name', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string {
		return _x( 'Empty Data', 'spam-reason-text', 'antispam-bee' );
	}
}
