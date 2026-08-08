<?php
/**
 * Delete a spam cron job.
 *
 * @package AntispamBee\Crons
 */

namespace AntispamBee\Crons;

use AntispamBee\GeneralOptions\DeleteOldSpam;
use AntispamBee\Helpers\Settings;

/**
 * Cron job to delete spam from the database.
 */
class DeleteSpamCron {

	const CRONJOB_NAME = 'asb_delete_spam_cronjob';

	/**
	 * Initialize the cron job.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action(
			'update_option_' . Settings::OPTION_NAME,
			[ __CLASS__, 'maybe_change_cron_state' ]
		);

		// Saving the settings is not the only way the option can change: a site upgraded from v2
		// has its configuration written by the migration, and the activation hook does not fire on
		// every update path. Reconciling on `init` makes the schedule converge on the setting
		// without the user having to re-save the settings page.
		add_action(
			'init',
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
	public static function maybe_change_cron_state(): void {
		if ( ! DeleteOldSpam::is_active() ) {
			self::unregister();

			return;
		}

		self::register();
	}

	/**
	 * Unregister this cron job.
	 *
	 * @return void
	 */
	public static function unregister(): void {
		if ( wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
	}

	/**
	 * Register (schedule) this cron job.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_schedule_event(
				time(),
				'daily',
				self::CRONJOB_NAME
			);
		}
	}

	/**
	 * Run the cron job's tasks.
	 * Delete spam from the database.
	 *
	 * @return void
	 */
	public static function run(): void {
		if ( ! defined( 'DOING_CRON' ) ) {
			return;
		}

		if ( ! DeleteOldSpam::is_active() ) {
			return;
		}

		$days = (int) Settings::get_option( DeleteOldSpam::get_option_name( 'delete_spam_cronjob_days' ) );
		if ( empty( $days ) ) {
			return;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE c, cm FROM `$wpdb->comments` AS c LEFT JOIN `$wpdb->commentmeta` AS cm ON (c.comment_ID = cm.comment_id) WHERE c.comment_approved = 'spam' AND SUBDATE(NOW(), %d) > c.comment_date_gmt",
				$days
			)
		);

		$wpdb->query( "OPTIMIZE TABLE `$wpdb->comments`" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}
}
