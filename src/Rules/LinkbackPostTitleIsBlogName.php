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
	 * Only linkbacks are supported.
	 *
	 * @var array
	 */
	protected static $supported_types = [ ContentTypeHelper::LINKBACK_TYPE ];

	/**
	 * Verify an item.
	 *
	 * Test if a linkback title is blog name.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$body      = isset( $item['comment_content'] ) ? $item['comment_content'] : null;
		$blog_name = isset( $item['comment_author'] ) ? $item['comment_author'] : null;
		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return 0;
		}

		return trim( $matches[1] ) === trim( $blog_name ) ? 999 : 0;
	}

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Linkback post title is blog name', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string {
		return _x( 'Linkback Post Title', 'spam-reason-text', 'antispam-bee' );
	}
}
