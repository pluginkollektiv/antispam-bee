<?php

namespace AntispamBee\Helpers;

use AntispamBee\Rules\Controllable;

class Settings {
	protected static $defaults;

	public static function init() {
		self::init_options();
		add_action( 'admin_menu', [ __CLASS__, 'add_sidebar_menu' ] );
	}

	protected static function init_options() {
		$defaults = [
			'options' => [
				'regexp_check'             => 1,
				'spam_ip'                  => 1,
				'already_commented'        => 1,
				'gravatar_check'           => 0,
				'time_check'               => 0,
				'ignore_pings'             => 0,

				'dashboard_chart'          => 0,
				'dashboard_count'          => 0,

				'country_code'             => 0,
				'country_denied'           => '',
				'country_allowed'          => '',

				'translate_api'            => 0,
				'translate_lang'           => [],

				'bbcode_check'             => 1,

				'flag_spam'                => 1,
				'email_notify'             => 0,
				'no_notice'                => 0,
				'cronjob_enable'           => 0,
				'cronjob_interval'         => 0,

				'ignore_filter'            => 0,
				'ignore_type'              => 0,

				'reasons_enable'           => 0,
				'ignore_reasons'           => [],

				'delete_data_on_uninstall' => 1,
			],
			'reasons' => [
				'css'           => esc_attr__( 'Honeypot', 'antispam-bee' ),
				'time'          => esc_attr__( 'Comment time', 'antispam-bee' ),
				'empty'         => esc_attr__( 'Empty Data', 'antispam-bee' ),
				'localdb'       => esc_attr__( 'Local DB Spam', 'antispam-bee' ),
				'server'        => esc_attr__( 'Fake IP', 'antispam-bee' ),
				'country'       => esc_attr__( 'Country Check', 'antispam-bee' ),
				'bbcode'        => esc_attr__( 'BBCode', 'antispam-bee' ),
				'lang'          => esc_attr__( 'Comment Language', 'antispam-bee' ),
				'regexp'        => esc_attr__( 'Regular Expression', 'antispam-bee' ),
				'title_is_name' => esc_attr__( 'Identical Post title and blog title', 'antispam-bee' ),
				'manually'      => esc_attr__( 'Manually', 'antispam-bee' ),
			],
		];

		self::$defaults = apply_filters( 'asb_default_options', $defaults );
	}

	public static function add_sidebar_menu() {
		$page = add_options_page(
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam_bee',
			[
				__CLASS__,
				'options_page',
			]
		);
	}

	public static function options_page() {
		?>
		<div class="wrap" id="ab_main">
			<h2>
				Antispam Bee
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes"/>

				<?php
				wp_nonce_field( '_antispam_bee__settings_nonce' );
				$rules = apply_filters( 'asb_rules', [] );
				foreach ( $rules as $rule ) {
					// todo: Check if valid rule, can also have a callable in key (tbd) instead of verifiable (like
					// implemented for the post processors).
					if ( ! isset( $rule['verifiable'] ) ) {
						continue;
					}

					$interfaces = class_implements( $rule['verifiable'] );
					if ( false === $interfaces || empty( $interfaces ) ) {
						continue;
					}

					if ( ! in_array( Controllable::class, $interfaces, true ) ) {
						continue;
					}

					render_option( $rule['verifiable'] );
				}
				?>
			</form>
		</div>
		<?php
	}

	private static function render_option( $verifiable ) {
	}

	/**
	 * Get all plugin options
	 *
	 * @return  array $options Array with option fields.
	 */
	public static function get_options() {
		$options = wp_cache_get( 'antispam_bee' );
		if ( ! $options ) {
			wp_cache_set(
				'antispam_bee',
				$options = get_option( 'antispam_bee' )
			);
		}

		if ( null === self::$defaults ) {
			self::init_options();
		}

		return wp_parse_args(
			$options,
			self::$defaults['options']
		);
	}

	/**
	 * Get single option field
	 *
	 * @param string $field Field name.
	 *
	 * @return  mixed         Field value.
	 * @since  0.1
	 * @since  2.4.2
	 */
	public static function get_option( $field ) {
		$options = self::get_options();

		return self::get_key( $options, $field );
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
		$options = get_option( 'antispam_bee' );

		if ( is_array( $options ) ) {
			$options = array_merge(
				$options,
				$data
			);
		} else {
			$options = $data;
		}

		update_option( 'antispam_bee', $options );
		wp_cache_set( 'antispam_bee', $options );
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
	 * @param string $key   Name of the key.
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
}
