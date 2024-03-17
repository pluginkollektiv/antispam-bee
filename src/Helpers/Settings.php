<?php

namespace AntispamBee\Helpers;

use AntispamBee\Handlers\PluginUpdate;

class Settings {
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

	// @todo: check if code is PHP 7 compatible
	const OPTION_NAME = 'antispam_bee_options';

	public static function init() {
		add_action(
			'update_option_' . self::OPTION_NAME,
			[ __CLASS__, 'update_cache' ],
			1,
			2
		);
	}

	public static function update_cache( $old_value, $value ) {
		wp_cache_set(
			self::OPTION_NAME,
			$value
		);
	}

	/**
	 * Get all plugin options
	 *
	 * @return  array $options Array with option fields.
	 */
	public static function get_options() {
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
	 * Get single option field
	 *
	 * @param string $option_name Option name.
	 * @param string $type The type.
	 *
	 * @return  mixed Field value.
	 */
	public static function get_option( $option_name, $type = 'general' ) {
		$options = self::get_options();

		$value_path = "$option_name";
		if ( ! empty( $type ) ) {
			$value_path = "$type.$option_name";
		}
		$value_path = str_replace( '-', '_', $value_path );

		return self::get_array_value_by_path( $value_path, $options );
	}

	/**
	 * Get value from array by path.
	 *
	 * @param string $path Dot-separated path to the wanted value.
	 * @param array  $array
	 *
	 * @return null|mixed
	 */
	public static function get_array_value_by_path( $path, $array ) {
		if ( ! is_array( $array ) ) {
			return null;
		}

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
	 * Update multiple option fields
	 *
	 * @param array $data Array with plugin option fields.
	 *
	 * @since  2.6.1
	 *
	 * @since  0.1
	 */
	public static function update_options( $data ) {
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
	 * Update single option field
	 *
	 * @param string $field Field name.
	 * @param mixed  $value The Field value.
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public static function update_option( $field, $value ) {
		self::update_options(
			[
				$field => $value,
			]
		);
	}

	/**
	 * Check and return an array key
	 *
	 * @param array  $array Array with values.
	 * @param string $key Name of the key.
	 *
	 * @return  mixed         Value of the requested key.
	 * @since   2.10.0 Only return `null` if option does not exist.
	 *
	 * @since   2.4.2
	 */
	public static function get_key( $array, $key ) {
		if ( empty( $array ) || empty( $key ) || ! isset( $array[ $key ] ) ) {
			return null;
		}

		return $array[ $key ];
	}

	public static function remove_array_key_by_path( $path, &$array ) {
		if ( ! is_array( $array ) ) {
			return;
		}

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

	private static function get_path_parts( $path ) {
		if ( ! is_string( $path ) ) {
			return [];
		}

		$path_parts = explode( '.', $path );
		if ( empty( $path_parts ) ) {
			return [];
		}

		return $path_parts;
	}

	public static function set_array_value_by_path( $path, $sanitized, &$options ) {
		if ( ! is_array( $options ) ) {
			return;
		}

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
