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
	 * Mirrors the matching WordPress core uses for the "previously approved
	 * commenter" discussion setting (see `wp_check_comment_data()`): an email
	 * address that belongs to a registered user is matched by user ID, and an
	 * anonymous commenter has to supply the same author name *and* email address
	 * that were approved before. Matching the email address alone would let anyone
	 * who knows or guesses an approved address inherit that trust.
	 *
	 * The payload is already unslashed, which is the form the comment columns are
	 * stored in, so both values are compared as they are.
	 *
	 * Handled payload attributes: `email`, `author`.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$email  = $item['email'] ?? '';
		$author = $item['author'] ?? '';

		if ( empty( $email ) || empty( $author ) ) {
			return 0;
		}

		$user = get_user_by( 'email', $email );

		if ( $user && ! empty( $user->ID ) ) {
			$approved_comments_count = get_comments(
				[
					'status'  => 'approve',
					'count'   => true,
					'user_id' => $user->ID,
				]
			);

			return $approved_comments_count > 0 ? -100 : 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		// `WP_Comment_Query` has no parameter for the author name, so the pair has to
		// be matched in SQL. Both values are passed as %s placeholders to
		// $wpdb->prepare(); the statement is otherwise a constant string.
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_author` = %s AND `comment_author_email` = %s LIMIT 1",
				$author,
				$email
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return empty( $result ) ? 0 : -100;
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
