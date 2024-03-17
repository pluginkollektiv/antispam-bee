<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\Settings;
use const AntispamBee\MAIN_PLUGIN_FILE;

/**
 * Runs, if needed, things after a plugin update.
 */
class PluginUpdate {
	/**
	 * Mapping of spam reason keys (key is pre-3.0, value 3.0 and later).
	 *
	 * @var array
	 */
	public static $spam_reasons_mapping = [
		'css'           => 'asb-honeypot',
		'time'          => 'asb-too-fast-submit',
		'empty'         => 'asb-empty',
		'localdb'       => 'asb-db-spam',
		// this is a reason we removed but need to keep because of old spam comments having that reason.
		'server'        => null,
		'country'       => 'asb-country-spam',
		'bbcode'        => 'asb-bbcode',
		'lang'          => 'asb-lang-spam',
		'regexp'        => 'asb-regexp',
		'title_is_name' => 'asb-linkback-post-title-is-blogname',
		'manually'      => 'asb-marked-manually',
	];

	private static $db_update_triggered = false;

	private static $db_version_is_current = null;

	/**
	 * Runs after Antispam Bee was upgraded.
	 */
	public static function maybe_run_plugin_updated_logic() {
		if ( self::db_version_is_current() || self::$db_update_triggered ) {
			return;
		}

		self::maybe_update_database();
	}

	/**
	 * Makes database changes, if needed.
	 */
	private static function maybe_update_database() {
		// Prevent further update triggers during the same request that run before the DB version is updated.
		self::$db_update_triggered = true;

		$version_from_db = get_option( 'antispambee_db_version', null );

		update_option( 'antispambee_db_version', self::get_plugin_version() );

		if ( $version_from_db === null ) {
			return;
		}

		if ( $version_from_db < 1.01 ) {
			global $wpdb;

			// In Version 2.9 the IP of the commenter was saved as a hash. We reverted this solution.
			// Therefore, we need to delete this unused data.
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$sql = 'delete from `' . $wpdb->commentmeta . '` where `meta_key` IN ("antispam_bee_iphash")';
			$wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		}

		// DB version was raised in ASB 2.10.0 to 1.02.
		if ( $version_from_db < 1.02 ) {
			// Update option names.
			$options = get_option( 'antispam_bee' );
			if ( isset( $options['country_black'] ) ) {
				$options['country_denied'] = $options['country_black'];
				unset( $options['country_black'] );
			}
			if ( isset( $options['country_white'] ) ) {
				$options['country_allowed'] = $options['country_white'];
				unset( $options['country_white'] );
			}

			update_option(
				'antispam_bee',
				$options
			);

			wp_cache_set(
				'antispam_bee',
				$options
			);
		}

		// DB version was raised in ASB 3.0.0 to 1.03.
		if ( version_compare(
			$version_from_db,
			'3.0.0-alpha.1',
			'<'
		) ) {
			// Update options (we migrate to a new option name `antispam_bee_options` in this release).
			$options = get_option( 'antispam_bee', [] );

			$allowed_languages = self::convert_multiselect_values( $options['translate_lang'] ?? [] );

			$delete_reasons = self::convert_multiselect_values( $options['ignore_reasons'] ?? [], self::$spam_reasons_mapping );

			$new_options = [
				'comment'    => [
					'post_processor_asb_delete_spam_active' => isset( $options['flag_spam'] ) && ! $options['flag_spam'] ? 'on' : '',
					'post_processor_asb_send_email_active' => $options['email_notify'] ? 'on' : '',
					'post_processor_asb_save_reason_active' => isset( $options['no_notice'] ) && ! $options['no_notice'] ? 'on' : '',
					'rule_asb_regexp_active'               => $options['regexp_check'] ? 'on' : '',
					'rule_asb_honeypot_active'             => 'on',
					'rule_asb_db_spam_active'              => $options['spam_ip'] ? 'on' : '',
					'rule_asb_approved_email_active'       => $options['already_commented'] ? 'on' : '',
					'rule_asb_too_fast_submit_active'      => $options['time_check'] ? 'on' : '',
					'post_processor_asb_delete_for_reasons_active' => $options['reasons_enable'] ? 'on' : '',
					'post_processor_asb_delete_for_reasons_reasons' => $delete_reasons,
					'rule_asb_bbcode_active'               => $options['bbcode_check'] ? 'on' : '',
					'rule_asb_valid_gravatar_active'       => $options['gravatar_check'] ? 'on' : '',
					'rule_asb_country_spam_active'         => $options['country_code'] ? 'on' : '',
					'rule_asb_country_spam_denied'         => $options['country_denied'] ?? '',
					'rule_asb_country_spam_allowed'        => $options['country_allowed'] ?? '',
					'rule_asb_lang_spam_active'            => $options['translate_api'] ? 'on' : '',
					'rule_asb_lang_spam_allowed'           => $allowed_languages,
				],
				'linkback'   => [
					'post_processor_asb_delete_spam_active' => isset( $options['flag_spam'] ) && ! $options['flag_spam'] ? 'on' : '',
					'post_processor_asb_send_email_active' => $options['email_notify'] ? 'on' : '',
					'post_processor_asb_save_reason_active' => isset( $options['no_notice'] ) && ! $options['no_notice'] ? 'on' : '',
					'rule_asb_regexp_active'               => $options['regexp_check'] ? 'on' : '',
					'rule_asb_db_spam_active'              => $options['spam_ip'] ? 'on' : '',
					'post_processor_asb_delete_for_reasons_active' => $options['reasons_enable'] ? 'on' : '',
					'post_processor_asb_delete_for_reasons_reasons' => $delete_reasons,
					'rule_asb_bbcode_active'               => $options['bbcode_check'] ? 'on' : '',
					'rule_asb_valid_gravatar_active'       => $options['gravatar_check'] ? 'on' : '',
					'rule_asb_country_spam_active'         => $options['country_code'] ? 'on' : '',
					'rule_asb_country_spam_denied'         => $options['country_denied'] ?? '',
					'rule_asb_country_spam_allowed'        => $options['country_allowed'] ?? '',
					'rule_asb_lang_spam_active'            => $options['translate_api'] ? 'on' : '',
					'rule_asb_lang_spam_allowed'           => $allowed_languages,
				],
				'general'    => [
					'general_delete_spam_cronjob_enabled_active' => $options['cronjob_enable'] ? 'on' : '',
					'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days' => $options['cronjob_interval'] ?? 30,
					'general_statistics_on_dashboard_active' => $options['dashboard_count'] ? 'on' : '',
					'general_ignore_linkbacks_active' => $options['ignore_pings'] ? 'on' : '',
					'general_delete_data_on_uninstall_active' => $options['delete_data_on_uninstall'] ? 'on' : '',
				],
				'spam_count' => $options['spam_count'] ?? 0,
			];

			update_option(
				Settings::OPTION_NAME,
				$new_options
			);

			wp_cache_set(
				Settings::OPTION_NAME,
				$new_options
			);
		}
	}

	private static function convert_multiselect_values( $values, $mapping = [] ) {
		if ( ! is_array( $values ) || empty( $values ) ) {
			return $values;
		}

		$flipped_values = array_flip( $values );
		$new_array      = [];
		foreach ( $flipped_values as $key => $value ) {
			if ( ! empty( $mapping ) && array_key_exists( $key, $mapping ) ) {
				$key = $mapping[ $key ];
			}
			$new_array[ $key ] = 'on';
		}

		return $new_array;
	}

	private static function get_plugin_version() {
		$meta = get_file_data( MAIN_PLUGIN_FILE, [ 'Version' => 'Version' ] );

		return $meta['Version'];
	}

	/**
	 * Whether the database structure is up-to-date.
	 *
	 * @return bool
	 */
	private static function db_version_is_current() {
		if ( ! is_null( self::$db_version_is_current ) ) {
			return self::$db_version_is_current;
		}

		self::$db_version_is_current = (bool) version_compare(
			get_option( 'antispambee_db_version', '1.0' ),
			self::get_plugin_version(),
			'=='
		);

		return self::$db_version_is_current;
	}
}
