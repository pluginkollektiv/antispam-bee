<?php
/**
 * Install the plugin.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Crons\DeleteSpamCron;
use AntispamBee\Helpers\Settings;

/**
 * Class Installer
 */
class PluginStateChangeHandler {

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

		if ( ! is_multisite() ) {
			self::remove_antispam_bee_data();
		}

		$site_ids = get_sites(
			array(
				'fields'                 => 'ids',
				'number'                 => 100,
				'update_site_cache'      => false,
				'update_site_meta_cache' => false,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			self::remove_antispam_bee_data();
			restore_current_blog();
		}

	}

	private static function remove_antispam_bee_data() {
		delete_option( Settings::OPTION_NAME );
		// @todo: do that when out of beta.
		// delete_option( 'antispam_bee' );

		global $wpdb;
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
