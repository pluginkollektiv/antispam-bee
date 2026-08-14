<?php
/**
 * Linkback Post Title is Blog Name Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking if the linkback post title is a blog name.
 */
class LinkbackPostTitleIsBlogName extends Base implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-linkback-post-title-is-blogname';

	/**
	 * A linkback titled like the blog name is a definitive spam signal.
	 *
	 * @var bool
	 */
	protected static $is_final = true;

	/**
	 * Only linkbacks are supported.
	 *
	 * @var string[]
	 */
	protected static $supported_types = [ ContentTypeHelper::LINKBACK_TYPE ];

	/**
	 * Verify an item.
	 *
	 * Test if a linkback title is blog name.
	 *
	 * Handled payload attributes: `body`, `author`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$body      = $item['body'] ?? null;
		$blog_name = $item['author'] ?? null;

		if ( ! is_string( $body ) || ! is_string( $blog_name ) ) {
			return 0;
		}

		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return 0;
		}

		return trim( $matches[1] ) === trim( $blog_name ) ? 999 : 0;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Linkback post title is blog name', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'Linkback Post Title', 'spam-reason-text', 'antispam-bee' );
	}
}
