<?php
/**
 * Install the plugin.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Class Installer
 */
class Installer {

	/**
	 * Activate callback.
	 */
	public static function activate() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'antispam_bee_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'antispam_bee_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::set_default_options();
		self::init_scheduled_hook();

		delete_transient( 'antispam_bee_installing' );

		flush_rewrite_rules();
	}

	/**
	 * Deactivate callback.
	 */
	public static function deactivate() {
		self::clear_scheduled_hook();
	}

	/**
	 * Uninstall callback.
	 */
	public static function uninstall() {
		if ( ! self::get_option( 'delete_data_on_uninstall' ) ) {
			return;
		}
		global $wpdb;

		delete_option( 'antispam_bee' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DELETE FROM `' . $wpdb->commentmeta . '`WHERE `meta_key` IN ("antispam_bee_iphash", "antispam_bee_reason")' );
	}

	/**
	 * Set some default antispam-bee options for new installs.
	 *
	 * @since 2.10.0 Set `use_output_buffer` option to `0`
	 */
	public static function set_default_options() {
		add_option( 'antispam_bee', [ 'use_output_buffer' => 0 ], '', 'no' );
	}

	/**
	 * Initialization of the cronjobs.
	 */
	public static function init_scheduled_hook() {
		if ( ! self::get_option( 'cronjob_enable' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'antispam_bee_daily_cronjob' ) ) {
			wp_schedule_event(
				time(),
				'daily',
				'antispam_bee_daily_cronjob'
			);
		}
	}

	/**
	 * Deletion of the cronjobs
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public static function clear_scheduled_hook() {
		if ( wp_next_scheduled( 'antispam_bee_daily_cronjob' ) ) {
			wp_clear_scheduled_hook( 'antispam_bee_daily_cronjob' );
		}
	}
}
