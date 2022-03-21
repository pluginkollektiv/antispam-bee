<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

class DbSpam implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		$params = [];
		$filter = [];
		$url = DataHelper::get_values_where_key_contains( [ 'url' ], $data );

		if ( ! empty( $url ) ) {
			$filter[] = '`comment_author_url` = %s';
			$params[] = wp_unslash( array_shift( $url ) );
		}
		$ip = DataHelper::get_values_by_keys( [ 'comment_author_IP' ], $data );

		if ( ! empty( $ip ) ) {
			$filter[] = '`comment_author_IP` = %s';
			$params[] = wp_unslash( array_shift( $ip ) );
		}

		$email = DataHelper::get_values_where_key_contains( [ 'email' ], $data );

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

	public static function get_name() {
		return __( 'Local DB Spam', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Look in the local spam database', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Check for spam data on your own blog', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1;
	}

	public static function get_slug() {
		return 'asb-db-spam';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return null;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}
}
