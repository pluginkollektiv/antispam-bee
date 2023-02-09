<?php
/**
 * Install the plugin.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use AntispamBee\Crons\DeleteSpamCron;

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

		self::init_scheduled_hook();
		flush_rewrite_rules();
	}

	/**
	 * Deactivate callback.
	 */
	public static function deactivate() {
		DeleteSpamCron::unregister();
		flush_rewrite_rules();
	}

	/**
	 * Uninstall callback.
	 */
	public static function uninstall() {
		if ( ! self::get_option( 'delete_data_on_uninstall' ) ) {
			return;
		}
		global $wpdb;

		delete_option( Settings::ANTISPAM_BEE_OPTION_NAME );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DELETE FROM `' . $wpdb->commentmeta . '`WHERE `meta_key` IN ("antispam_bee_iphash", "antispam_bee_reason")' );
	}

	/**
	 * Initialization of the cronjobs.
	 */
	public static function init_scheduled_hook() {
		DeleteSpamCron::maybe_change_cron_state();
	}
}
