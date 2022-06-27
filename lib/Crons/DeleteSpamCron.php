<?php

namespace AntispamBee\Crons;

use AntispamBee\Helpers\Settings;

class DeleteSpamCron {

	const CRONJOB_NAME = 'asb_delete_spam_cronjob';

	public static function init() {
		add_action(
			'update_option_' . Settings::ANTISPAM_BEE_OPTION_NAME,
			[ __CLASS__, 'maybe_change_cron_state' ]
		);

		add_action(
			self::CRONJOB_NAME,
			[
				__CLASS__,
				'run',
			]
		);
	}

	public static function maybe_change_cron_state() {
		if ( ! Settings::get_option( 'delete_spam_cronjob_enabled' ) ) {
			self::unregister();
			return;
		}

		self::register();
	}

	public static function register() {
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_schedule_event(
				time(),
				'daily',
				self::CRONJOB_NAME
			);
		}
	}

	public static function unregister() {
		if ( wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
	}

	public static function run() {
		if ( ! defined( 'DOING_CRON' ) ) {
			return;
		}

		if ( ! Settings::get_option( 'delete_spam_cronjob_enabled' ) ) {
			return;
		}

		$days = (int) Settings::get_option( 'delete_spam_cronjob_days' );
		error_log( $days, 3, trailingslashit( ANTISPAM_BEE_PATH ) . 'error.log' );
		if ( empty( $days ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE c, cm FROM `$wpdb->comments` AS c LEFT JOIN `$wpdb->commentmeta` AS cm ON (c.comment_ID = cm.comment_id) WHERE c.comment_approved = 'spam' AND SUBDATE(NOW(), %d) > c.comment_date_gmt",
				$days
			)
		);

		$wpdb->query( "OPTIMIZE TABLE `$wpdb->comments`" );
	}
}
