<?php
/**
 * Settings helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use AntispamBee\Handlers\PluginUpdate;

// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound

/**
 * Settings helper.
 */
class Settings {

	const OPTION_NAME = 'antispam_bee_options';
	/**
	 * Default options.
	 *
	 * @var array[]
	 */
	protected static $defaults = [
		'comment'  => [
			'rule_asb_regexp_active'                => 'on',
			'rule_asb_honeypot_active'              => 'on',
			'rule_asb_db_spam_active'               => 'on',
			'rule_asb_bbcode_active'                => 'on',
			'post_processor_asb_save_reason_active' => 'on',
			'rule_asb_approved_email_active'        => 'on',
		],
		'linkback' => [
			'rule_asb_regexp_active'                => 'on',
			'rule_asb_db_spam_active'               => 'on',
			'rule_asb_bbcode_active'                => 'on',
			'post_processor_asb_save_reason_active' => 'on',
		],
		'general'  => [
			'general_delete_data_on_uninstall_active' => 'on',
		],
	];

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action(
			'update_option_' . self::OPTION_NAME,
			[ __CLASS__, 'update_cache' ],
			1,
			2
		);
	}

	/**
	 * Update the cache.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 *
	 * @return void
	 */
	public static function update_cache( $old_value, $value ): void {
		wp_cache_set( self::OPTION_NAME, $value );
	}

	/**
	 * Get a single option field.
	 *
	 * @param string $option_name   Option name.
	 * @param string $reaction_type The reaction type.
	 *
	 * @return mixed Field value.
	 */
	public static function get_option( string $option_name, string $reaction_type = 'general' ) {
		$options = self::get_options();

		$value_path = "$option_name";
		if ( ! empty( $reaction_type ) ) {
			$value_path = "$reaction_type.$option_name";
		}
		$value_path = str_replace( '-', '_', $value_path );

		return self::get_array_value_by_path( $value_path, $options );
	}

	/**
	 * Get all plugin options.
	 *
	 * @return array An array with option fields.
	 */
	public static function get_options(): array {
		PluginUpdate::maybe_run_plugin_updated_logic();
		$options = wp_cache_get( self::OPTION_NAME );
		if ( $options ) {
			return $options;
		}

		$options = get_option( self::OPTION_NAME, self::$defaults );
		wp_cache_set( self::OPTION_NAME, $options );

		return $options;
	}

	/**
	 * Get the value from an array by path.
	 *
	 * @param string $path  Dot-separated path to the wanted value.
	 * @param array  $array Options array.
	 *
	 * @return null|mixed Value at given path, if present.
	 */
	public static function get_array_value_by_path( string $path, array $array ) {
		$path_array = self::get_path_parts( $path );
		if ( empty( $path_array ) ) {
			return null;
		}

		$option_value = $array;

		foreach ( $path_array as $path_part ) {
			if ( ! isset( $option_value[ $path_part ] ) ) {
				return null;
			}

			$option_value = $option_value[ $path_part ];
		}

		return $option_value;
	}

	/**
	 * Get the path parts from a dot-separated notation.
	 *
	 * @param mixed $path Dot-separated path to the wanted value.
	 *
	 * @return string[] The path parts.
	 */
	private static function get_path_parts( $path ): array {
		if ( ! is_string( $path ) ) {
			return [];
		}

		return explode( '.', $path );
	}

	/**
	 * Update a single option field.
	 *
	 * @param string $field Field name.
	 * @param mixed  $value The field value.
	 */
	public static function update_option( string $field, $value ): void {
		self::update_options(
			[
				$field => $value,
			]
		);
	}

	/**
	 * Update multiple option fields.
	 *
	 * @param array $data An array with plugin option fields.
	 */
	public static function update_options( array $data ): void {
		$options = get_option( self::OPTION_NAME );

		if ( is_array( $options ) ) {
			$options = array_merge(
				$options,
				$data
			);
		} else {
			$options = $data;
		}

		update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Check and return an array key.
	 *
	 * @param array  $array An array with values.
	 * @param string $key   The name of the key.
	 *
	 * @return  mixed The value of the requested key.
	 */
	public static function get_key( array $array, string $key ) {
		if ( empty( $array ) || empty( $key ) || ! isset( $array[ $key ] ) ) {
			return null;
		}

		return $array[ $key ];
	}

	/**
	 * Remove array item(s) by key.
	 *
	 * @param string $path  Dot-separated path to the wanted value.
	 * @param array  $array The array to filter.
	 *
	 * @return void
	 */
	public static function remove_array_key_by_path( string $path, array &$array ): void {
		$path_parts = self::get_path_parts( $path );
		if ( empty( $path_parts ) ) {
			return;
		}

		$tmp      = &$array;
		$last_key = array_key_last( $path_parts );
		foreach ( $path_parts as $key => $value ) {
			if ( $key === $last_key ) {
				unset( $tmp[ $value ] );
				break;
			}

			if ( isset( $tmp[ $value ] ) ) {
				$tmp = &$tmp[ $value ];
			}
		}
	}

	/**
	 * Set an array item at a given path.
	 *
	 * @param string $path      Dot-separated path to the wanted value.
	 * @param mixed  $sanitized Sanitized value.
	 * @param array  $options   Options array to process.
	 *
	 * @return void
	 */
	public static function set_array_value_by_path( string $path, $sanitized, array &$options ): void {
		if ( null === $sanitized ) {
			return;
		}

		$path_parts = self::get_path_parts( $path );
		if ( empty( $path_parts ) ) {
			return;
		}

		$last_key = array_key_last( $path_parts );
		$tmp      = &$options;
		foreach ( $path_parts as $key => $value ) {
			if ( $key === $last_key ) {
				$tmp[ $value ] = $sanitized;
				break;
			}

			if ( ! isset( $tmp[ $value ] ) ) {
				$tmp[ $value ] = null;
			}

			$tmp = &$tmp[ $value ];
		}
	}
}
