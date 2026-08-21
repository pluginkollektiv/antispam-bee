<?php
/**
 * Plugin Update handler.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\Settings;
use const AntispamBee\MAIN_PLUGIN_FILE;

/**
 * Runs, if needed, things after a plugin update.
 */
class PluginUpdate {
	/**
	 * Name of the option holding the database version.
	 */
	const DB_VERSION_OPTION_NAME = 'antispambee_db_version';

	/**
	 * Mapping of spam reason keys (key is pre-3.0, value 3.0 and later).
	 *
	 * @var array<string, string|null>
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

	/**
	 * Was DB update triggered?
	 *
	 * @var bool
	 */
	private static $db_update_triggered = false;

	/**
	 * Is DB at current version?
	 *
	 * @var bool|null
	 */
	private static $db_version_is_current = null;

	/**
	 * Run after Antispam Bee was upgraded.
	 */
	public static function maybe_run_plugin_updated_logic(): void {
		if ( self::db_version_is_current() || self::$db_update_triggered ) {
			return;
		}

		self::maybe_update_database();
	}

	/**
	 * Whether the database structure is up-to-date.
	 *
	 * @return bool Whether the database structure is up-to-date.
	 */
	private static function db_version_is_current(): bool {
		if ( ! is_null( self::$db_version_is_current ) ) {
			return self::$db_version_is_current;
		}

		self::$db_version_is_current = (bool) version_compare(
			get_option( self::DB_VERSION_OPTION_NAME, '1.0' ),
			self::get_plugin_version(),
			'=='
		);

		return self::$db_version_is_current;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string The plugin version.
	 */
	private static function get_plugin_version(): string {
		$meta = get_file_data( MAIN_PLUGIN_FILE, [ 'Version' => 'Version' ] );

		return $meta['Version'];
	}

	/**
	 * Make database changes, if needed.
	 */
	private static function maybe_update_database(): void {
		// Prevent further update triggers during the same request that run before the DB version is updated.
		self::$db_update_triggered = true;

		$version_from_db = get_option( self::DB_VERSION_OPTION_NAME, null );

		/*
		 * `null` is a fresh install, which has nothing to migrate. A recorded revision
		 * that is not a scalar cannot be compared against at all, and since the version
		 * write below no longer happens first, retrying such a value would now fatal on
		 * every request rather than once. Neither case has a migration to run, so both
		 * fall through to the version write.
		 */
		if ( is_scalar( $version_from_db ) ) {
			static::run_migration_steps( (string) $version_from_db );
		}

		/*
		 * Only now that every step has completed. Writing the version first would spend
		 * the one chance a site gets: `db_version_is_current()` would report the database
		 * as up-to-date on every later request, so a step interrupted by a fatal, a DB
		 * error, a timeout or a warning promoted to an exception would never run again.
		 * The legacy option would still be in place while `antispam_bee_options` was
		 * never written, leaving the site on `Settings::$defaults` — the user's entire
		 * configuration gone, with no way to retrigger the migration short of editing
		 * the option by hand.
		 *
		 * Deferring the write cannot make the migration run twice within a request,
		 * because `self::$db_update_triggered` is already set above.
		 */
		update_option( self::DB_VERSION_OPTION_NAME, self::get_plugin_version() );
	}

	/**
	 * Bring an existing install up to the current database revision.
	 *
	 * Called for an install that has a recorded revision; a fresh install has nothing
	 * to migrate and only gets the revision written.
	 *
	 * Every step has to tolerate running against an install that already passed it: a
	 * later step may fail and bring the whole method back on the next request.
	 *
	 * @param string $version_from_db The database revision the install is on.
	 *
	 * @return void
	 */
	protected static function run_migration_steps( string $version_from_db ): void {
		if ( $version_from_db < 1.01 ) {
			global $wpdb;

			// In Version 2.9 the IP of the commenter was saved as a hash. We reverted this solution.
			// Therefore, we need to delete this unused data.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// The statement is a constant string; the only interpolated value is the table name from $wpdb.
			// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
			$sql = 'DELETE FROM `' . $wpdb->commentmeta . '` WHERE `meta_key` IN ("antispam_bee_iphash")';
			$wpdb->query( $sql );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
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
			if ( ! is_array( $options ) ) {
				$options = [];
			}

			$allowed_languages = self::convert_multiselect_values( $options['translate_lang'] ?? [] );

			$delete_reasons = self::convert_multiselect_values( $options['ignore_reasons'] ?? [], self::$spam_reasons_mapping );

			/*
			 * Every legacy flag is read through `empty()`, because the legacy option array grew
			 * over the lifetime of Antispam Bee 2.x and a site that never re-saved its settings
			 * after an upgrade can hold an array that predates any given key. Reading such a key
			 * directly emits an `Undefined array key` warning, which aborts the whole migration on
			 * installs that promote warnings to exceptions — and since the database version is
			 * raised before the migration runs, it is then never retried. A missing key means the
			 * feature did not exist yet, so it is migrated as disabled.
			 */
			$new_options = [
				'comment'    => [
					'post_processor_asb_delete_spam_active' => isset( $options['flag_spam'] ) && ! $options['flag_spam'] ? 'on' : '',
					'post_processor_asb_send_email_active' => empty( $options['email_notify'] ) ? '' : 'on',
					'post_processor_asb_save_reason_active' => isset( $options['no_notice'] ) && ! $options['no_notice'] ? 'on' : '',
					'rule_asb_regexp_active'               => empty( $options['regexp_check'] ) ? '' : 'on',
					'rule_asb_honeypot_active'             => 'on',
					'rule_asb_db_spam_active'              => empty( $options['spam_ip'] ) ? '' : 'on',
					'rule_asb_approved_email_active'       => empty( $options['already_commented'] ) ? '' : 'on',
					'rule_asb_too_fast_submit_active'      => empty( $options['time_check'] ) ? '' : 'on',
					'post_processor_asb_delete_for_reasons_active' => empty( $options['reasons_enable'] ) ? '' : 'on',
					'post_processor_asb_delete_for_reasons_reasons' => $delete_reasons,
					'rule_asb_bbcode_active'               => empty( $options['bbcode_check'] ) ? '' : 'on',
					'rule_asb_valid_gravatar_active'       => empty( $options['gravatar_check'] ) ? '' : 'on',
					'rule_asb_country_spam_active'         => empty( $options['country_code'] ) ? '' : 'on',
					'rule_asb_country_spam_denied'         => $options['country_denied'] ?? '',
					'rule_asb_country_spam_allowed'        => $options['country_allowed'] ?? '',
					'rule_asb_lang_spam_active'            => empty( $options['translate_api'] ) ? '' : 'on',
					'rule_asb_lang_spam_allowed'           => $allowed_languages,
				],
				'linkback'   => [
					'post_processor_asb_delete_spam_active' => isset( $options['flag_spam'] ) && ! $options['flag_spam'] ? 'on' : '',
					'post_processor_asb_send_email_active' => empty( $options['email_notify'] ) ? '' : 'on',
					'post_processor_asb_save_reason_active' => isset( $options['no_notice'] ) && ! $options['no_notice'] ? 'on' : '',
					'rule_asb_regexp_active'               => empty( $options['regexp_check'] ) ? '' : 'on',
					'rule_asb_db_spam_active'              => empty( $options['spam_ip'] ) ? '' : 'on',
					'post_processor_asb_delete_for_reasons_active' => empty( $options['reasons_enable'] ) ? '' : 'on',
					'post_processor_asb_delete_for_reasons_reasons' => $delete_reasons,
					'rule_asb_bbcode_active'               => empty( $options['bbcode_check'] ) ? '' : 'on',
					'rule_asb_valid_gravatar_active'       => empty( $options['gravatar_check'] ) ? '' : 'on',
					'rule_asb_country_spam_active'         => empty( $options['country_code'] ) ? '' : 'on',
					'rule_asb_country_spam_denied'         => $options['country_denied'] ?? '',
					'rule_asb_country_spam_allowed'        => $options['country_allowed'] ?? '',
					'rule_asb_lang_spam_active'            => empty( $options['translate_api'] ) ? '' : 'on',
					'rule_asb_lang_spam_allowed'           => $allowed_languages,
				],
				'general'    => [
					'general_delete_spam_cronjob_enabled_active' => empty( $options['cronjob_enable'] ) ? '' : 'on',
					'general_delete_spam_cronjob_enabled_delete_spam_cronjob_days' => $options['cronjob_interval'] ?? 30,
					'general_statistics_on_dashboard_active' => empty( $options['dashboard_count'] ) ? '' : 'on',
					'general_ignore_linkbacks_active' => empty( $options['ignore_pings'] ) ? '' : 'on',
					'general_delete_data_on_uninstall_active' => empty( $options['delete_data_on_uninstall'] ) ? '' : 'on',
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

	/**
	 * Normalize a legacy multiselect option into a list of non-empty strings.
	 *
	 * These options were single-value settings before the multiselect rework, so a
	 * 2.x install that never re-saved its settings can still hold a plain string
	 * (`'translate_lang' => 'de'`) or an empty string where an array is expected.
	 * PHP does not coerce a scalar into an `array` parameter even in weak mode, so
	 * such a value used to raise an uncaught `TypeError`. 2.x itself cast at every
	 * read site; this restores that tolerance.
	 *
	 * @param mixed $values Raw legacy option value.
	 *
	 * @return string[] Normalized list of selected values.
	 */
	private static function normalize_multiselect_values( $values ): array {
		if ( ! is_array( $values ) ) {
			$values = is_scalar( $values ) ? [ $values ] : [];
		}

		$normalized = [];
		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$value = (string) $value;
			if ( '' !== $value ) {
				$normalized[] = $value;
			}
		}

		return $normalized;
	}

	/**
	 * Convert multiselect values.
	 * Takes an array of selected keys, applies optional mapping and generates a new array using
	 * these values as keys and "on" as value.
	 *
	 * @param mixed                      $values  Selected values, in whatever shape the legacy option holds.
	 * @param array<string, string|null> $mapping Key mapping (optional).
	 *
	 * @return array<string, string> Converted array of selected options.
	 */
	private static function convert_multiselect_values( $values, array $mapping = [] ): array {
		$values = self::normalize_multiselect_values( $values );

		if ( empty( $values ) ) {
			return $values;
		}

		$flipped_values = array_flip( $values );
		$new_array      = [];
		foreach ( $flipped_values as $key => $value ) {
			if ( ! empty( $mapping ) && array_key_exists( $key, $mapping ) ) {
				$key = $mapping[ $key ];
			}
			if ( null === $key ) {
				continue;
			}
			$new_array[ $key ] = 'on';
		}

		return $new_array;
	}
}
