<?php
/**
 * Install the plugin.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use WP_Roles;

/**
 * Class Installer
 */
class Installer {

	/**
	 * Activate Antispam Bee.
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


	public static function deactivate() {
		self::clear_scheduled_hook();
	}

	public static function uninstall() {
		if ( ! self::get_option( 'delete_data_on_uninstall' ) ) {
			return;
		}
		global $wpdb;

		delete_option( 'antispam_bee' );

		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = 'DELETE FROM `' . $wpdb->commentmeta . '`WHERE `meta_key` IN ("antispam_bee_iphash", "antispam_bee_reason")';
		$wpdb->query( $sql );
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
