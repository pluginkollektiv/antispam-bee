<?php
/**
 * Approved Email Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;

/**
 * Checks if the email is from an already approved commenter.
 */
class ApprovedEmail extends ControllableBase {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-approved-email';

	/**
	 * Only comments are supported.
	 *
	 * @var string[]
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];

	/**
	 * Verify an item.
	 *
	 * Handled payload attributes: `email`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$email = $item['email'] ?? '';
		if ( empty( $email ) ) {
			return 0;
		}

		$approved_comments_count = get_comments(
			[
				'status'       => 'approve',
				'count'        => true,
				'author_email' => $email,
			]
		);

		if ( 0 === $approved_comments_count ) {
			return 0;
		}

		return -100;
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Approved Email', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Trust approved commenters', 'antispam-bee' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string|null The rule description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'No review of already commented users', 'antispam-bee' );
	}
}
