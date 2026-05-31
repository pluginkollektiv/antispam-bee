<?php
/**
 * DB Spam Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Checks the spam comments database entries to find matching patterns.
 */
class DbSpam extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-db-spam';

	/**
	 * Verify an item.
	 *
	 * Test item for spam patterns from database.
	 *
	 * @param array $item Item to verify.
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		$params = [];
		$filter = [];
		$values = DataHelper::get_values_where_key_contains( [ 'url' ], $item );
		$url    = wp_unslash( array_shift( $values ) );

		if ( ! empty( $url ) ) {
			$filter[] = '`comment_author_url` = %s';
			$params[] = $url;
		}

		$ip = DataHelper::get_values_by_keys( [ 'comment_author_IP' ], $item );

		if ( ! empty( $ip ) ) {
			$filter[] = '`comment_author_IP` = %s';
			$params[] = wp_unslash( array_shift( $ip ) );
		}

		$email = DataHelper::get_values_where_key_contains( [ 'email' ], $item );

		if ( ! empty( $email ) ) {
			$filter[] = '`comment_author_email` = %s';
			$params[] = wp_unslash( array_shift( $email ) );
		}
		if ( empty( $params ) ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$filter_sql = implode( ' OR ', $filter );

		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = 'spam' AND (%s) LIMIT 1",
					$filter_sql
				),
				$params
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return (int) ! empty( $result );
	}

	/**
	 * Get rule name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Local DB Spam', 'antispam-bee' );
	}

	/**
	 * Get rule label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'Look in the local spam database', 'antispam-bee' );
	}

	/**
	 * Get rule description.
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return __( 'Check for spam data on your own blog', 'antispam-bee' );
	}

	/**
	 * Get human-readable spam reason.
	 *
	 * @return string
	 */
	public static function get_reason_text(): string {
		return __( 'Local DB', 'antispam-bee' );
	}
}
