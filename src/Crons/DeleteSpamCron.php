<?php
/**
 * Delete spam cron job.
 *
 * @package AntispamBee\Crons
 */

namespace AntispamBee\Crons;

use AntispamBee\Helpers\Settings;
use const AntispamBee\PLUGIN_PATH;

/**
 * Cron job to delete spam from database.
 */
class DeleteSpamCron {

	const CRONJOB_NAME = 'asb_delete_spam_cronjob';

	/**
	 * Initialize the cron job.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'update_option_' . Settings::OPTION_NAME,
			[ __CLASS__, 'maybe_change_cron_state' ]
		);

		add_action(
			self::CRONJOB_NAME,
			[ __CLASS__, 'run' ]
		);
	}

	/**
	 * Register the cron job, if is actually enabled.
	 *
	 * @return void
	 */
	public static function maybe_change_cron_state() {
		if ( ! Settings::get_option( 'delete_spam_cronjob_enabled' ) ) {
			self::unregister();

			return;
		}

		self::register();
	}

	/**
	 * Register (schedule) this cron job.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_schedule_event(
				time(),
				'daily',
				self::CRONJOB_NAME
			);
		}
	}

	/**
	 * Unregister this cron job.
	 *
	 * @return void
	 */
	public static function unregister() {
		if ( wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
	}

	/**
	 * Run the cron job's tasks.
	 * Delete spam from database.
	 *
	 * @return void
	 */
	public static function run() {
		if ( ! defined( 'DOING_CRON' ) ) {
			return;
		}

		if ( ! Settings::get_option( 'delete_spam_cronjob_enabled' ) ) {
			return;
		}

		$days = (int) Settings::get_option( 'delete_spam_cronjob_days' );
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
