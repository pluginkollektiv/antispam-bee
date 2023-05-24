<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\Settings;
use const AntispamBee\ANTISPAM_BEE_FILE;

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
		'title_is_name' => 'asb-title-is-blogname',
		'manually'      => 'asb-marked-manually',
	];

	/**
	 * Runs after Antispam Bee was upgraded.
	 */
	public static function maybe_run_plugin_updated_logic() {
		// Todo: we need to check if the migration is working, so we exit early for now.
		return;
		if (self::db_version_is_current()) {
			return;
		}

		self::maybe_update_database();
	}

	/**
	 * Makes database changes, if needed.
	 */
	private static function maybe_update_database() {
		$version_from_db = get_option( 'antispambee_db_version', 0 );
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
		if ( $version_from_db < 1.03 ) {
			// Update options (we migrate to a new option name `antispam_bee_options` in this release).
			$options = get_option( 'antispam_bee' );

			if ( isset( $options['ignore_reasons'] ) && is_array( $options['ignore_reasons'] ) ) {
				foreach ( $options['ignore_reasons'] as $key => $reason ) {
					$value = $options['ignore_reasons'][ $key ];
					unset( $options['ignore_reasons'][ $key ] );

					if ( ! isset( self::$spam_reasons_mapping[ $key ] ) ) {
						continue;
					}

					$options['ignore_reasons'][ self::$spam_reasons_mapping[ $key ] ] = $value;
				}
			}

			$new_options = [
				'comment'   => [
					'post_processor_asb_delete_spam_active'         => isset( $options['flag_spam'] ) && ! $options['flag_spam'],
					'post_processor_asb_send_email_active'          => $options['email_notify'] ?? false,
					'post_processor_asb_save_reason_active'         => isset( $options['no_notice'] ) && ! $options['no_notice'],
					'rule_asb_regexp_active'                        => $options['regexp_check'] ?? false,
					'rule_asb_honeypot_active'                      => 'on',
					'rule_asb_db_spam_active'                       => $options['spam_ip'] ?? false,
					'rule_asb_approved_email_active'                => $options['already_commented'] ?? false,
					'rule_asb_too_fast_submit_active'               => $options['time_check'] ?? false,
					'post_processor_asb_delete_for_reasons_reasons' => $options['ignore_reasons'] ?? [],
					'rule_asb_bbcode_active'                        => $options['gravatar_check'] ?? true,
					'rule_asb_valid_gravatar_active'                => $options['gravatar_check'] ?? false,
					'rule_asb_country_spam_active'                  => $options['country_code'] ?? false,
					'rule_asb_country_spam_denied'                  => $options['country_denied'] ?? '',
					'rule_asb_country_spam_allowed'                 => $options['country_allowed'] ?? '',
					'rule_asb_lang_spam_active'                     => $options['translate_api'] ?? false,
					'rule_asb_lang_spam_allowed'                    => $options['translate_api'] ?? [],
				],
				'linkback' => [
					'post_processor_asb_delete_spam_active'         => isset( $options['flag_spam'] ) && ! $options['flag_spam'],
					'post_processor_asb_send_email_active'          => $options['email_notify'] ?? false,
					'post_processor_asb_save_reason_active'         => isset( $options['no_notice'] ) && ! $options['no_notice'],
					'rule_asb_regexp_active'                        => $options['regexp_check'] ?? false,
					'rule_asb_db_spam_active'                       => $options['spam_ip'] ?? false,
					'post_processor_asb_delete_for_reasons_reasons' => $options['ignore_reasons'] ?? [],
					'rule_asb_bbcode_active'                        => $options['gravatar_check'] ?? true,
					'rule_asb_valid_gravatar_active'                => $options['gravatar_check'] ?? false,
					'rule_asb_lang_spam_active'                     => $options['translate_api'] ?? false,
					'rule_asb_lang_spam_allowed'                    => $options['translate_api'] ?? [],
				],
				'general'   => [
					'general_delete_spam_cronjob_enabled_active'                   => $options['cronjob_enable'] ?? false,
					'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days' => $options['cronjob_interval'] ?? 0,
					'general_statistics_on_dashboard_count'              => $options['dashboard_count'] ?? 0,
					'general_ignore_pings_active'                                  => $options['ignore_pings'] ?? false,
					'general_delete_data_on_uninstall_active'                      => $options['delete_data_on_uninstall'] ?? false,
				],
			];

			update_option(
				Settings::ANTISPAM_BEE_OPTION_NAME,
				$new_options
			);

			wp_cache_set(
				Settings::ANTISPAM_BEE_OPTION_NAME,
				$new_options
			);
		}

		update_option( 'antispambee_db_version', self::get_plugin_version() );
	}

	private static function get_plugin_version() {
		$meta = get_file_data( ANTISPAM_BEE_FILE, [ 'Version' => 'Version' ] );

		return $meta['Version'];
	}

	/**
	 * Whether the database structure is up-to-date.
	 *
	 * @return bool
	 */
	private static function db_version_is_current() {
		return (bool) version_compare(
			get_option( 'antispambee_db_version', '1.0' ),
			self::get_plugin_version(),
			'=='
		);
	}
}
