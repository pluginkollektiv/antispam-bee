<?php
/**
 * Plugin Name: Antispam Bee
 * Description: Antispam plugin with a sophisticated toolset for effective day to day comment and trackback spam-fighting. Built with data protection and privacy in mind.
 * Author:      pluginkollektiv
 * Author URI:  https://pluginkollektiv.org
 * Plugin URI:  https://wordpress.org/plugins/antispam-bee/
 * Text Domain: antispam-bee
 * Domain Path: /lang
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version:     2.9.1
 *
 * @package Antispam Bee
 **/

/*
* Copyright (C)  2009-2015 Sergej Müller
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/


// Make sure this file is only run from within the WordPress context.
defined( 'ABSPATH' ) || exit;

/**
 * Antispam_Bee
 *
 * @since   0.1
 * @change  2.4
 */
class Antispam_Bee {

	/**
	 * The option defaults.
	 *
	 * @var array
	 */
	public static $defaults;

	/**
	 * Which internal datastructure version we are running on.
	 *
	 * @var int
	 */
	private static $db_version = 1;

	/**
	 * The base.
	 *
	 * @var string
	 */
	private static $_base;

	/**
	 * The salt.
	 *
	 * @var string
	 */
	private static $_salt;

	/**
	 * The spam reason.
	 *
	 * @var string
	 */
	private static $_reason;

	/**
	 * The current Post ID.
	 *
	 * @var int
	 */
	private static $_current_post_id;

	/**
	 * "Constructor" of the class
	 *
	 * @since   0.1
	 * @change  2.6.4
	 */
	public static function init() {
		add_action(
			'unspam_comment',
			array(
				__CLASS__,
				'delete_spam_reason_by_comment',
			)
		);

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		self::_init_internal_vars();

		if ( defined( 'DOING_CRON' ) ) {
			add_action(
				'antispam_bee_daily_cronjob',
				array(
					__CLASS__,
					'start_daily_cronjob',
				)
			);

		} elseif ( is_admin() ) {
			add_action(
				'admin_menu',
				array(
					__CLASS__,
					'add_sidebar_menu',
				)
			);

			if ( self::_current_page( 'dashboard' ) ) {
				add_action(
					'init',
					array(
						__CLASS__,
						'load_plugin_lang',
					)
				);
				add_filter(
					'dashboard_glance_items',
					array(
						__CLASS__,
						'add_dashboard_count',
					)
				);
				add_action(
					'wp_dashboard_setup',
					array(
						__CLASS__,
						'add_dashboard_chart',
					)
				);

			} elseif ( self::_current_page( 'plugins' ) ) {
				add_action(
					'init',
					array(
						__CLASS__,
						'load_plugin_lang',
					)
				);
				add_filter(
					'plugin_row_meta',
					array(
						__CLASS__,
						'init_row_meta',
					),
					10,
					2
				);
				add_filter(
					'plugin_action_links_' . self::$_base,
					array(
						__CLASS__,
						'init_action_links',
					)
				);

			} elseif ( self::_current_page( 'options' ) ) {
				add_action(
					'admin_init',
					array(
						__CLASS__,
						'load_plugin_lang',
					)
				);
				add_action(
					'admin_init',
					array(
						__CLASS__,
						'init_plugin_sources',
					)
				);
				add_action(
					'admin_init',
					array(
						__CLASS__,
						'update_database',
					)
				);

			} elseif ( self::_current_page( 'admin-post' ) ) {
				require_once dirname( __FILE__ ) . '/inc/gui.class.php';

				add_action(
					'admin_post_ab_save_changes',
					array(
						'Antispam_Bee_GUI',
						'save_changes',
					)
				);

			} elseif ( self::_current_page( 'edit-comments' ) ) {
				// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
				if ( ! empty( $_GET['comment_status'] ) && 'spam' === $_GET['comment_status'] && ! self::get_option( 'no_notice' ) ) {
					// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
					require_once dirname( __FILE__ ) . '/inc/columns.class.php';

					self::load_plugin_lang();

					add_filter(
						'manage_edit-comments_columns',
						array(
							'Antispam_Bee_Columns',
							'register_plugin_columns',
						)
					);
					add_filter(
						'manage_comments_custom_column',
						array(
							'Antispam_Bee_Columns',
							'print_plugin_column',
						),
						10,
						2
					);
					add_filter(
						'admin_print_styles-edit-comments.php',
						array(
							'Antispam_Bee_Columns',
							'print_column_styles',
						)
					);

					add_filter(
						'manage_edit-comments_sortable_columns',
						array(
							'Antispam_Bee_Columns',
							'register_sortable_columns',
						)
					);
					add_action(
						'pre_get_posts',
						array(
							'Antispam_Bee_Columns',
							'set_orderby_query',
						)
					);
				}
			}
		} else {
			add_action(
				'wp',
				array(
					__CLASS__,
					'populate_post_id',
				)
			);

			// Save IP hash, if comment is spam.
			add_action(
				'comment_post',
				array(
					__CLASS__,
					'save_ip_hash',
				),
				10,
				1
			);

			add_action(
				'template_redirect',
				array(
					__CLASS__,
					'prepare_comment_field',
				)
			);
			add_action(
				'init',
				array(
					__CLASS__,
					'precheck_incoming_request',
				)
			);
			add_action(
				'preprocess_comment',
				array(
					__CLASS__,
					'handle_incoming_request',
				),
				1
			);
			add_action(
				'antispam_bee_count',
				array(
					__CLASS__,
					'the_spam_count',
				)
			);
		}
	}



	/*
	*   ############################
	*   ########  INSTALL  #########
	*   ############################
	*/

	/**
	 * Action during the activation of the Plugins
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function activate() {
		add_option(
			'antispam_bee',
			array(),
			'',
			'no'
		);

		if ( self::get_option( 'cronjob_enable' ) ) {
			self::init_scheduled_hook();
		}
	}


	/**
	 * Action to deactivate the plugin
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function deactivate() {
		self::clear_scheduled_hook();
	}


	/**
	 * Action deleting the plugin
	 *
	 * @since   2.4
	 * @change  2.4
	 */
	public static function uninstall() {
		if ( ! self::get_option( 'delete_data_on_uninstall' ) ) {
			return;
		}
		global $wpdb;

		delete_option( 'antispam_bee' );
		$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );

		//phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$sql = 'delete from `' . $wpdb->commentmeta . '` where `meta_key` IN ("antispam_bee_iphash", "antispam_bee_reason")';
		$wpdb->query( $sql );
		//phpcs:enable WordPress.WP.PreparedSQL.NotPrepared
	}



	/*
	*   ############################
	*   ########  INTERNAL  ########
	*   ############################
	*/

	/**
	 * Initialization of the internal variables
	 *
	 * @since   2.4
	 * @change  2.7.0
	 */
	private static function _init_internal_vars() {
		self::$_base = plugin_basename( __FILE__ );

		$salt        = defined( 'NONCE_SALT' ) ? NONCE_SALT : ABSPATH;
		self::$_salt = substr( sha1( $salt ), 0, 10 );

		self::$defaults = array(
			'options' => array(
				'advanced_check'           => 1,
				'regexp_check'             => 1,
				'spam_ip'                  => 1,
				'already_commented'        => 1,
				'gravatar_check'           => 0,
				'time_check'               => 0,
				'ignore_pings'             => 0,
				'always_allowed'           => 0,

				'dashboard_chart'          => 0,
				'dashboard_count'          => 0,

				'country_code'             => 0,
				'country_black'            => '',
				'country_white'            => '',

				'translate_api'            => 0,
				'translate_lang'           => array(),

				'bbcode_check'             => 1,

				'flag_spam'                => 1,
				'email_notify'             => 0,
				'no_notice'                => 0,
				'cronjob_enable'           => 0,
				'cronjob_interval'         => 0,

				'ignore_filter'            => 0,
				'ignore_type'              => 0,

				'reasons_enable'           => 0,
				'ignore_reasons'           => array(),

				'delete_data_on_uninstall' => 1,
			),
			'reasons' => array(
				'css'           => esc_attr__( 'Honeypot', 'antispam-bee' ),
				'time'          => esc_attr__( 'Comment time', 'antispam-bee' ),
				'empty'         => esc_attr__( 'Empty Data', 'antispam-bee' ),
				'server'        => esc_attr__( 'Fake IP', 'antispam-bee' ),
				'localdb'       => esc_attr__( 'Local DB Spam', 'antispam-bee' ),
				'country'       => esc_attr__( 'Country Check', 'antispam-bee' ),
				'bbcode'        => esc_attr__( 'BBCode', 'antispam-bee' ),
				'lang'          => esc_attr__( 'Comment Language', 'antispam-bee' ),
				'regexp'        => esc_attr__( 'Regular Expression', 'antispam-bee' ),
				'title_is_name' => esc_attr__( 'Identical Post title and blog title', 'antispam-bee' ),
			),
		);
	}

	/**
	 * Check and return an array key
	 *
	 * @since   2.4.2
	 * @change  2.4.2
	 *
	 * @param   array  $array Array with values.
	 * @param   string $key   Name of the key.
	 * @return  mixed         Value of the requested key.
	 */
	public static function get_key( $array, $key ) {
		if ( empty( $array ) || empty( $key ) || empty( $array[ $key ] ) ) {
			return null;
		}

		return $array[ $key ];
	}


	/**
	 * Localization of the admin pages
	 *
	 * @since   0.1
	 * @change  2.4
	 *
	 * @param   string $page Mark the page.
	 * @return  boolean      True on success.
	 */
	private static function _current_page( $page ) {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		switch ( $page ) {
			case 'dashboard':
				return ( empty( $GLOBALS['pagenow'] ) || ( ! empty( $GLOBALS['pagenow'] ) && 'index.php' === $GLOBALS['pagenow'] ) );

			case 'options':
				return ( ! empty( $_GET['page'] ) && 'antispam_bee' === $_GET['page'] );

			case 'plugins':
				return ( ! empty( $GLOBALS['pagenow'] ) && 'plugins.php' === $GLOBALS['pagenow'] );

			case 'admin-post':
				return ( ! empty( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] );

			case 'edit-comments':
				return ( ! empty( $GLOBALS['pagenow'] ) && 'edit-comments.php' === $GLOBALS['pagenow'] );

			default:
				return false;
		}
		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
	}


	/**
	 * Integration of the localization file
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function load_plugin_lang() {
		load_plugin_textdomain(
			'antispam-bee'
		);
	}


	/**
	 * Add the link to the settings
	 *
	 * @since   1.1
	 * @change  1.1
	 *
	 * @param array $data The action link array.
	 * @return array $data The action link array.
	 */
	public static function init_action_links( $data ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'page' => 'antispam_bee',
						),
						admin_url( 'options-general.php' )
					),
					esc_attr__( 'Settings', 'antispam-bee' )
				),
			)
		);
	}

	/**
	 * Meta links of the plugin
	 *
	 * @since   0.1
	 * @change  2.6.2
	 *
	 * @param   array  $input Existing links.
	 * @param   string $file  Current page.
	 * @return  array  $data  Modified links.
	 */
	public static function init_row_meta( $input, $file ) {
		if ( $file !== self::$_base ) {
			return $input;
		}

		return array_merge(
			$input,
			array(
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'antispam-bee' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/antispam-bee" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'antispam-bee' ) . '</a>',
			)
		);
	}

	/*
	*   ############################
	*   #######  RESOURCES  ########
	*   ############################
	*/

	/**
	 * Registration of resources (CSS & JS)
	 *
	 * @since   1.6
	 * @change  2.4.5
	 */
	public static function init_plugin_sources() {
		$plugin = get_plugin_data( __FILE__ );

		wp_register_script(
			'ab_script',
			plugins_url( 'js/scripts.min.js', __FILE__ ),
			array( 'jquery' ),
			$plugin['Version']
		);

		wp_register_style(
			'ab_style',
			plugins_url( 'css/styles.min.css', __FILE__ ),
			array( 'dashicons' ),
			$plugin['Version']
		);
	}


	/**
	 * Initialization of the option page
	 *
	 * @since   0.1
	 * @change  2.4.3
	 */
	public static function add_sidebar_menu() {
		$page = add_options_page(
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam_bee',
			array(
				'Antispam_Bee_GUI',
				'options_page',
			)
		);

		add_action(
			'admin_print_scripts-' . $page,
			array(
				__CLASS__,
				'add_options_script',
			)
		);

		add_action(
			'admin_print_styles-' . $page,
			array(
				__CLASS__,
				'add_options_style',
			)
		);

		add_action(
			'load-' . $page,
			array(
				__CLASS__,
				'init_options_page',
			)
		);
	}


	/**
	 * Initialization of JavaScript
	 *
	 * @since   1.6
	 * @change  2.4
	 */
	public static function add_options_script() {
		wp_enqueue_script( 'ab_script' );
	}


	/**
	 * Initialization of Stylesheets
	 *
	 * @since   1.6
	 * @change  2.4
	 */
	public static function add_options_style() {
		wp_enqueue_style( 'ab_style' );
	}


	/**
	 * Integration of the GUI
	 *
	 * @since   2.4
	 * @change  2.4
	 */
	public static function init_options_page() {
		require_once dirname( __FILE__ ) . '/inc/gui.class.php';
	}



	/*
	*   ############################
	*   #######  DASHBOARD  ########
	*   ############################
	*/

	/**
	 * Display the spam counter on the dashboard
	 *
	 * @since   0.1
	 * @change  2.6.5
	 *
	 * @param   array $items  Initial array with dashboard items.
	 * @return  array $items  Merged array with dashboard items.
	 */
	public static function add_dashboard_count( $items = array() ) {
		if ( ! current_user_can( 'manage_options' ) || ! self::get_option( 'dashboard_count' ) ) {
			return $items;
		}

		echo '<style>#dashboard_right_now .ab-count:before {content: "\f117"}</style>';

		$items[] = '<span class="ab-count">' . esc_html(
			sprintf(
				// translators: The number of spam comments Antispam Bee blocked so far.
				__( '%d Blocked', 'antispam-bee' ),
				self::_get_spam_count()
			)
		) . '</span>';

		return $items;
	}

	/**
	 * Initialize the dashboard chart
	 *
	 * @since   1.9
	 * @change  2.5.6
	 */
	public static function add_dashboard_chart() {
		if ( ! current_user_can( 'publish_posts' ) || ! self::get_option( 'dashboard_chart' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'ab_widget',
			'Antispam Bee',
			array(
				__CLASS__,
				'show_spam_chart',
			)
		);

		add_action(
			'admin_head',
			array(
				__CLASS__,
				'add_dashboard_style',
			)
		);
	}

	/**
	 * Print dashboard styles
	 *
	 * @since   1.9.0
	 * @change  2.5.8
	 */
	public static function add_dashboard_style() {
		$plugin = get_plugin_data( __FILE__ );

		wp_register_style(
			'ab_chart',
			plugins_url( 'css/dashboard.min.css', __FILE__ ),
			array(),
			$plugin['Version']
		);

		wp_print_styles( 'ab_chart' );
	}


	/**
	 * Print dashboard scripts
	 *
	 * @since   1.9.0
	 * @change  2.5.8
	 */
	public static function add_dashboard_script() {
		if ( ! self::get_option( 'daily_stats' ) ) {
			return;
		}

		$plugin = get_plugin_data( __FILE__ );

		wp_enqueue_script(
			'raphael',
			plugins_url( 'js/raphael.min.js', __FILE__ ),
			array(),
			'2.1.0',
			true
		);

		wp_enqueue_script(
			'ab-raphael',
			plugins_url( 'js/raphael.helper.min.js', __FILE__ ),
			array( 'raphael' ),
			$plugin['Version'],
			true
		);

		wp_enqueue_script(
			'ab_chart_js',
			plugins_url( 'js/dashboard.min.js', __FILE__ ),
			array( 'jquery', 'ab-raphael' ),
			$plugin['Version'],
			true
		);
	}

	/**
	 * Print dashboard html
	 *
	 * @since   1.9.0
	 * @change  2.5.8
	 */
	public static function show_spam_chart() {
		$items = (array) self::get_option( 'daily_stats' );

		if ( empty( $items ) ) {
			echo sprintf(
				'<div id="ab_chart"><p>%s</p></div>',
				esc_html__( 'No data available.', 'antispam-bee' )
			);

			return;
		}

		self::add_dashboard_script();

		ksort( $items, SORT_NUMERIC );

		$html = "<table id=ab_chart_data>\n";

		$html .= "<tfoot><tr>\n";
		foreach ( $items as $date => $count ) {
			$html .= '<th>' . date_i18n( 'j. F Y', $date ) . "</th>\n";
		}
		$html .= "</tr></tfoot>\n";

		$html .= "<tbody><tr>\n";
		foreach ( $items as $date => $count ) {
			$html .= '<td>' . (int) $count . "</td>\n";
		}
		$html .= "</tr></tbody>\n";

		$html .= "</table>\n";

		echo wp_kses_post( '<div id="ab_chart">' . $html . '</div>' );
	}

	/*
	*   ############################
	*   ########  OPTIONS  #########
	*   ############################
	*/

	/**
	 * Get all plugin options
	 *
	 * @since   2.4
	 * @change  2.6.1
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

		return wp_parse_args(
			$options,
			self::$defaults['options']
		);
	}

	/**
	 * Get single option field
	 *
	 * @since   0.1
	 * @change  2.4.2
	 *
	 * @param   string $field Field name.
	 * @return  mixed         Field value.
	 */
	public static function get_option( $field ) {
		$options = self::get_options();

		return self::get_key( $options, $field );
	}


	/**
	 * Update single option field
	 *
	 * @since   0.1
	 * @change  2.4
	 *
	 * @param   string $field Field name.
	 * @param   mixed  $value The Field value.
	 */
	private static function _update_option( $field, $value ) {
		self::update_options(
			array(
				$field => $value,
			)
		);
	}


	/**
	 * Update multiple option fields
	 *
	 * @since   0.1
	 * @change  2.6.1
	 *
	 * @param   array $data Array with plugin option fields.
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

		update_option(
			'antispam_bee',
			$options
		);

		wp_cache_set(
			'antispam_bee',
			$options
		);
	}



	/*
	*   ############################
	*   ########  CRONJOBS  ########
	*   ############################
	*/

	/**
	 * Execution of the daily cronjobs
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function start_daily_cronjob() {
		if ( ! self::get_option( 'cronjob_enable' ) ) {
			return;
		}

		self::_update_option(
			'cronjob_timestamp',
			time()
		);

		self::_delete_old_spam();
	}


	/**
	 * Delete old spam comments
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	private static function _delete_old_spam() {
		$days = (int) self::get_option( 'cronjob_interval' );

		if ( empty( $days ) ) {
			return false;
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->comments` WHERE `comment_approved` = 'spam' AND SUBDATE(NOW(), %d) > comment_date_gmt",
				$days
			)
		);

		$wpdb->query( "OPTIMIZE TABLE `$wpdb->comments`" );
	}


	/**
	 * Initialization of the cronjobs
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function init_scheduled_hook() {
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
	 * @since   0.1
	 * @change  2.4
	 */
	public static function clear_scheduled_hook() {
		if ( wp_next_scheduled( 'antispam_bee_daily_cronjob' ) ) {
			wp_clear_scheduled_hook( 'antispam_bee_daily_cronjob' );
		}
	}



	/*
	*   ############################
	*   ######  SPAM CHECK  ########
	*   ############################
	*/

	/**
	 * Check POST values
	 *
	 * @since   0.1
	 * @change  2.6.3
	 */
	public static function precheck_incoming_request() {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( is_feed() || is_trackback() || empty( $_POST ) || self::_is_mobile() ) {
			return;
		}

		$request_uri  = self::get_key( $_SERVER, 'REQUEST_URI' );
		$request_path = self::parse_url( $request_uri, 'path' );

		if ( strpos( $request_path, 'wp-comments-post.php' ) === false ) {
			return;
		}

		$post_id      = (int) self::get_key( $_POST, 'comment_post_ID' );
		$hidden_field = self::get_key( $_POST, 'comment' );
		$plugin_field = self::get_key( $_POST, self::get_secret_name_for_post( $post_id ) );

		if ( empty( $hidden_field ) && ! empty( $plugin_field ) ) {
			$_POST['comment'] = $plugin_field;
			unset( $_POST[ self::get_secret_name_for_post( $post_id ) ] );
		} else {
			$_POST['ab_spam__hidden_field'] = 1;
		}
		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
	}


	/**
	 * Check incoming requests for spam
	 *
	 * @since   0.1
	 * @change  2.6.3
	 *
	 * @param   array $comment  Untreated comment.
	 * @return  array $comment  Treated comment.
	 */
	public static function handle_incoming_request( $comment ) {
		$comment['comment_author_IP'] = self::get_client_ip();

		$request_uri  = self::get_key( $_SERVER, 'REQUEST_URI' );
		$request_path = self::parse_url( $request_uri, 'path' );

		if ( empty( $request_path ) ) {
			return self::_handle_spam_request(
				$comment,
				'empty'
			);
		}

		$ping = array(
			'types'   => array( 'pingback', 'trackback', 'pings' ),
			'allowed' => ! self::get_option( 'ignore_pings' ),
		);

		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		// Everybody can post.
		if ( strpos( $request_path, 'wp-comments-post.php' ) !== false && ! empty( $_POST ) ) {
			// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
			$status = self::_verify_comment_request( $comment );

			if ( ! empty( $status['reason'] ) ) {
				return self::_handle_spam_request(
					$comment,
					$status['reason']
				);
			}
		} elseif ( in_array( self::get_key( $comment, 'comment_type' ), $ping['types'], true ) && $ping['allowed'] ) {
			$status = self::_verify_trackback_request( $comment );

			if ( ! empty( $status['reason'] ) ) {
				return self::_handle_spam_request(
					$comment,
					$status['reason'],
					true
				);
			}
		}

		return $comment;
	}


	/**
	 * Prepares the replacement of the comment field
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function prepare_comment_field() {
		if ( is_feed() || is_trackback() || is_robots() || self::_is_mobile() ) {
			return;
		}

		if ( ! is_singular() && ! self::get_option( 'always_allowed' ) ) {
			return;
		}

		ob_start(
			array(
				'Antispam_Bee',
				'replace_comment_field',
			)
		);
	}


	/**
	 * Replaces the comment field
	 *
	 * @since   2.4
	 * @change  2.6.4
	 *
	 * @param   string $data HTML code of the website.
	 * @return  string       Treated HTML code.
	 */
	public static function replace_comment_field( $data ) {
		if ( empty( $data ) ) {
			return;
		}

		if ( ! preg_match( '#<textarea.+?name=["\']comment["\']#s', $data ) ) {
			return $data;
		}

		return preg_replace_callback(
			'/(?P<all>                                                              (?# match the whole textarea tag )
				<textarea                                                           (?# the opening of the textarea and some optional attributes )
				(                                                                   (?# match a id attribute followed by some optional ones and the name attribute )
					(?P<before1>[^>]*)
					(?P<id1>id=["\'](?P<id_value1>[^>"\']*)["\'])
					(?P<between1>[^>]*)
					name=["\']comment["\']
					|                                                               (?# match same as before, but with the name attribute before the id attribute )
					(?P<before2>[^>]*)
					name=["\']comment["\']
					(?P<between2>[^>]*)
					(?P<id2>id=["\'](?P<id_value2>[^>"\']*)["\'])
					|                                                               (?# match same as before, but with no id attribute )
					(?P<before3>[^>]*)
					name=["\']comment["\']
					(?P<between3>[^>]*)
				)
				(?P<after>[^>]*)                                                    (?# match any additional optional attributes )
				>                                                                   (?# the closing of the textarea opening tag )
				(?s)(?P<content>.*?)                                                (?# any textarea content )
				<\/textarea>                                                        (?# the closing textarea tag )
			)/x',
			array( 'Antispam_Bee', 'replace_comment_field_callback' ),
			$data,
			-1
		);
	}

	/**
	 * The callback function for the preg_match_callback to modify the textarea tags.
	 *
	 * @since   2.6.10
	 *
	 * @param array $matches The regex matches.
	 *
	 * @return string The modified content string.
	 */
	public static function replace_comment_field_callback( $matches ) {
		if ( self::get_option( 'time_check' ) ) {
			$init_time_field = sprintf(
				'<input type="hidden" name="ab_init_time" value="%d" />',
				time()
			);
		} else {
			$init_time_field = '';
		}

		$output = '<textarea autocomplete="nope" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];

		$id_script = '';
		if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
			$output   .= 'id="' . self::get_secret_id_for_post( self::$_current_post_id ) . '" ';
			$id_script = '<script type="text/javascript">document.getElementById("comment").setAttribute( "id", "a' . substr( esc_js( md5( time() ) ), 0, 31 ) . '" );document.getElementById("' . esc_js( self::get_secret_id_for_post( self::$_current_post_id ) ) . '").setAttribute( "id", "comment" );</script>';
		}

		$output .= ' name="' . esc_attr( self::get_secret_name_for_post( self::$_current_post_id ) ) . '" ';
		$output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
		$output .= $matches['after'] . '>';
		$output .= $matches['content'];
		$output .= '</textarea><textarea id="comment" aria-hidden="true" name="comment" autocomplete="nope" style="padding:0;clip:rect(1px, 1px, 1px, 1px);position:absolute !important;white-space:nowrap;height:1px;width:1px;overflow:hidden;" tabindex="-1"></textarea>';

		$output .= $id_script;
		$output .= $init_time_field;

		return $output;
	}


	/**
	 * Check the trackbacks
	 *
	 * @since   2.4
	 * @change  2.7.0
	 *
	 * @param   array $comment Trackback data.
	 * @return  array          Array with suspected reason.
	 */
	private static function _verify_trackback_request( $comment ) {
		$ip        = self::get_key( $comment, 'comment_author_IP' );
		$url       = self::get_key( $comment, 'comment_author_url' );
		$body      = self::get_key( $comment, 'comment_content' );
		$post_id   = self::get_key( $comment, 'comment_post_ID' );
		$type      = self::get_key( $comment, 'comment_type' );
		$blog_name = self::get_key( $comment, 'comment_author' );

		if ( empty( $url ) || empty( $body ) ) {
			return array(
				'reason' => 'empty',
			);
		}

		if ( empty( $ip ) ) {
			return array(
				'reason' => 'empty',
			);
		}

		if ( 'pingback' === $type && self::_pingback_from_myself( $url, $post_id ) ) {
			return;
		}

		if ( self::is_trackback_post_title_blog_name_spam( $body, $blog_name ) ) {
			return array(
				'reason' => 'title_is_name',
			);
		}

		$options = self::get_options();

		if ( $options['bbcode_check'] && self::_is_bbcode_spam( $body ) ) {
			return array(
				'reason' => 'bbcode',
			);
		}

		if ( $options['advanced_check'] && self::_is_fake_ip( $ip, self::parse_url( $url, 'host' ) ) ) {
			return array(
				'reason' => 'server',
			);
		}

		if ( $options['spam_ip'] && self::_is_db_spam( $ip, $url ) ) {
			return array(
				'reason' => 'localdb',
			);
		}

		if ( $options['country_code'] && self::_is_country_spam( $ip ) ) {
			return array(
				'reason' => 'country',
			);
		}

		if ( $options['translate_api'] && self::_is_lang_spam( $body ) ) {
			return array(
				'reason' => 'lang',
			);
		}

		if ( $options['regexp_check'] && self::_is_regexp_spam(
			array(
				'ip'     => $ip,
				'rawurl' => $url,
				'host'   => self::parse_url( $url, 'host' ),
				'body'   => $body,
				'email'  => '',
				'author' => '',
			)
		) ) {
			return array(
				'reason' => 'regexp',
			);
		}
	}

	/**
	 * Check, if I pinged myself.
	 *
	 * @since 2.8.2
	 *
	 * @param string $url            The URL from where the ping came.
	 * @param int    $target_post_id The post ID which has been pinged.
	 *
	 * @return bool
	 */
	private static function _pingback_from_myself( $url, $target_post_id ) {

		if ( 0 !== strpos( $url, home_url() ) ) {
			return false;
		}

		$original_post_id = (int) url_to_postid( $url );
		if ( ! $original_post_id ) {
			return false;
		}

		$post = get_post( $original_post_id );
		if ( ! $post ) {
			return false;
		}

		$urls        = wp_extract_urls( $post->post_content );
		$url_to_find = get_permalink( $target_post_id );
		if ( ! $url_to_find ) {
			return false;
		}
		foreach ( $urls as $url ) {
			if ( strpos( $url, $url_to_find ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check the comment
	 *
	 * @since   2.4
	 * @change  2.7.0
	 *
	 * @param   array $comment Data of the comment.
	 * @return  array|void     Array with suspected reason
	 */
	private static function _verify_comment_request( $comment ) {
		$ip     = self::get_key( $comment, 'comment_author_IP' );
		$url    = self::get_key( $comment, 'comment_author_url' );
		$body   = self::get_key( $comment, 'comment_content' );
		$email  = self::get_key( $comment, 'comment_author_email' );
		$author = self::get_key( $comment, 'comment_author' );

		if ( empty( $body ) ) {
			return array(
				'reason' => 'empty',
			);
		}

		if ( empty( $ip ) ) {
			return array(
				'reason' => 'empty',
			);
		}

		if ( get_option( 'require_name_email' ) && ( empty( $email ) || empty( $author ) ) ) {
			return array(
				'reason' => 'empty',
			);
		}

		$options = self::get_options();

		if ( $options['already_commented'] && ! empty( $email ) && self::_is_approved_email( $email ) ) {
			return;
		}

		if ( $options['gravatar_check'] && ! empty( $email ) && 1 === (int) get_option( 'show_avatars', 0 ) && self::_has_valid_gravatar( $email ) ) {
			return;
		}

		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( ! empty( $_POST['ab_spam__hidden_field'] ) ) {
			return array(
				'reason' => 'css',
			);
		}
		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( $options['time_check'] && self::_is_shortest_time() ) {
			return array(
				'reason' => 'time',
			);
		}

		if ( $options['bbcode_check'] && self::_is_bbcode_spam( $body ) ) {
			return array(
				'reason' => 'bbcode',
			);
		}

		if ( $options['advanced_check'] && self::_is_fake_ip( $ip ) ) {
			return array(
				'reason' => 'server',
			);
		}

		if ( $options['regexp_check'] && self::_is_regexp_spam(
			array(
				'ip'     => $ip,
				'rawurl' => $url,
				'host'   => self::parse_url( $url, 'host' ),
				'body'   => $body,
				'email'  => $email,
				'author' => $author,
			)
		) ) {
			return array(
				'reason' => 'regexp',
			);
		}

		if ( $options['spam_ip'] && self::_is_db_spam( $ip, $url, $email ) ) {
			return array(
				'reason' => 'localdb',
			);
		}

		if ( $options['country_code'] && self::_is_country_spam( $ip ) ) {
			return array(
				'reason' => 'country',
			);
		}

		if ( $options['translate_api'] && self::_is_lang_spam( $body ) ) {
			return array(
				'reason' => 'lang',
			);
		}
	}


	/**
	 * Check for a Gravatar image
	 *
	 * @since   2.6.5
	 * @change  2.6.5
	 *
	 * @param   string $email Input email.
	 * @return  boolean       Check status (true = Gravatar available).
	 */
	private static function _has_valid_gravatar( $email ) {
		$response = wp_safe_remote_get(
			sprintf(
				'https://www.gravatar.com/avatar/%s?d=404',
				md5( strtolower( trim( $email ) ) )
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			return true;
		}

		return false;
	}


	/**
	 * Check for comment action time
	 *
	 * @since   2.6.4
	 * @change  2.6.4
	 *
	 * @return  boolean    TRUE if the action time is less than 5 seconds
	 */
	private static function _is_shortest_time() {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		// Everybody can Post.
		$init_time = (int) self::get_key( $_POST, 'ab_init_time' );
		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( 0 === $init_time ) {
			return false;
		}

		if ( time() - $init_time < apply_filters( 'ab_action_time_limit', 5 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the blog name and the title of the blog post from which the trackback originates are equal.
	 *
	 * @since   2.6.4
	 *
	 * @param string $body      The comment body.
	 * @param string $blog_name The name of the blog.
	 *
	 * @return bool
	 */
	private static function is_trackback_post_title_blog_name_spam( $body, $blog_name ) {
		preg_match( '/<strong>(.*)<\/strong>\\n\\n/', $body, $matches );
		if ( ! isset( $matches[1] ) ) {
			return false;
		}
		return trim( $matches[1] ) === trim( $blog_name );
	}


	/**
	 * Usage of regexp, also custom
	 *
	 * @since   2.5.2
	 * @change  2.5.6
	 *
	 * @param   array $comment Array with commentary data.
	 * @return  boolean        True for suspicious comment.
	 */
	private static function _is_regexp_spam( $comment ) {
		$fields = array(
			'ip',
			'host',
			'body',
			'email',
			'author',
		);

		$patterns = array(
			array(
				'host'  => '^(www\.)?\d+\w+\.com$',
				'body'  => '^\w+\s\d+$',
				'email' => '@gmail.com$',
			),
			array(
				'body' => '\<\!.+?mfunc.+?\>',
			),
			array(
				'author' => 'moncler|north face|vuitton|handbag|burberry|outlet|prada|cialis|viagra|maillot|oakley|ralph lauren|ray ban|iphone|プラダ',
			),
			array(
				'host' => '^(www\.)?fkbook\.co\.uk$|^(www\.)?nsru\.net$|^(www\.)?goo\.gl$|^(www\.)?bit\.ly$',
			),
			array(
				'body' => 'target[t]?ed (visitors|traffic)|viagra|cialis',
			),
			array(
				'body' => 'purchase amazing|buy amazing',
			),
			array(
				'body'  => 'dating|sex|lotto|pharmacy',
				'email' => '@mail\.ru|@yandex\.',
			),
		);

		$quoted_author = preg_quote( $comment['author'], '/' );
		if ( $quoted_author ) {
			$patterns[] = array(
				'body' => sprintf(
					'<a.+?>%s<\/a>$',
					$quoted_author
				),
			);
			$patterns[] = array(
				'body' => sprintf(
					'%s https?:.+?$',
					$quoted_author
				),
			);
			$patterns[] = array(
				'email'  => '@gmail.com$',
				'author' => '^[a-z0-9-\.]+\.[a-z]{2,6}$',
				'host'   => sprintf(
					'^%s$',
					$quoted_author
				),
			);
		}

		$patterns = apply_filters(
			'antispam_bee_patterns',
			$patterns
		);

		if ( ! $patterns ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			$hits = array();

			foreach ( $pattern as $field => $regexp ) {
				if ( empty( $field ) || ! in_array( $field, $fields, true ) || empty( $regexp ) ) {
					continue;
				}

				$comment[ $field ] = ( function_exists( 'iconv' ) ? iconv( 'utf-8', 'utf-8//TRANSLIT', $comment[ $field ] ) : $comment[ $field ] );

				if ( empty( $comment[ $field ] ) ) {
					continue;
				}

				if ( preg_match( '/' . $regexp . '/isu', $comment[ $field ] ) ) {
					$hits[ $field ] = true;
				}
			}

			if ( count( $hits ) === count( $pattern ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Review a comment on its existence in the local spam
	 *
	 * @since   2.0.0
	 * @change  2.5.4
	 *
	 * @param   string $ip    Comment IP.
	 * @param   string $url   Comment URL (optional).
	 * @param   string $email Comment Email (optional).
	 * @return  boolean       True for suspicious comment.
	 */
	private static function _is_db_spam( $ip, $url = '', $email = '' ) {
		global $wpdb;

		$params = array();
		$filter = array();
		if ( ! empty( $url ) ) {
			$filter[] = '`comment_author_url` = %s';
			$params[] = wp_unslash( $url );
		}
		if ( ! empty( $ip ) ) {
			$filter[] = '`comment_author_IP` = %s';
			$params[] = wp_unslash( $ip );
		}

		if ( ! empty( $email ) ) {
			$filter[] = '`comment_author_email` = %s';
			$params[] = wp_unslash( $email );
		}
		if ( empty( $params ) ) {
			return false;
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$filter_sql = implode( ' OR ', $filter );

		$result = $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = 'spam' AND (%s) LIMIT 1",
					$filter_sql
				),
				$params
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:enable WordPress.WP.PreparedSQL.NotPrepared

		return ! empty( $result );
	}


	/**
	 * Check for country spam by (anonymized) IP
	 *
	 * @since   2.6.9
	 * @change  2.6.9
	 *
	 * @param   string $ip IP address.
	 * @return  boolean    True if the comment is spam based on country filter.
	 */
	private static function _is_country_spam( $ip ) {
		$options = self::get_options();

		$white = preg_split(
			'/[\s,;]+/',
			$options['country_white'],
			-1,
			PREG_SPLIT_NO_EMPTY
		);
		$black = preg_split(
			'/[\s,;]+/',
			$options['country_black'],
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		if ( empty( $white ) && empty( $black ) ) {
			return false;
		}

		$response = wp_safe_remote_head(
			esc_url_raw(
				sprintf(
					'https://api.ip2country.info/ip?%s',
					self::_anonymize_ip( $ip )
				),
				'https'
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$country = (string) wp_remote_retrieve_header( $response, 'x-country-code' );

		if ( empty( $country ) || strlen( $country ) !== 2 ) {
			return false;
		}

		if ( ! empty( $black ) ) {
			return ( in_array( $country, $black, true ) );
		}

		return ( ! in_array( $country, $white, true ) );
	}


	/**
	 * Check for BBCode spam
	 *
	 * @since   2.5.1
	 * @change  2.5.1
	 *
	 * @param   string $body Content of a comment.
	 * @return  boolean      True for BBCode in content
	 */
	private static function _is_bbcode_spam( $body ) {
		return (bool) preg_match( '/\[url[=\]].*\[\/url\]/is', $body );
	}


	/**
	 * Check for an already approved e-mail address
	 *
	 * @since   2.0
	 * @change  2.5.1
	 *
	 * @param   string $email E-mail address.
	 * @return  boolean       True for a found entry.
	 */
	private static function _is_approved_email( $email ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_author_email` = %s LIMIT 1",
				wp_unslash( $email )
			)
		);

		if ( $result ) {
			return true;
		}

		return false;
	}


	/**
	 * Check for a fake IP
	 *
	 * @since   2.0
	 * @change  2.6.2
	 *
	 * @param   string $client_ip    Client IP.
	 * @param   string $client_host  Client Host (optional).
	 * @return  boolean              True if fake IP.
	 */
	private static function _is_fake_ip( $client_ip, $client_host = '' ) {
		$host_by_ip = gethostbyaddr( $client_ip );

		if ( self::_is_ipv6( $client_ip ) ) {
			return $client_ip !== $host_by_ip;
		}

		if ( empty( $client_host ) ) {
			$ip_by_host = gethostbyname( $host_by_ip );

			if ( $ip_by_host === $host_by_ip ) {
				return false;
			}
		} else {
			if ( $host_by_ip === $client_ip ) {
				return true;
			}

			$ip_by_host = gethostbyname( $client_host );
		}

		if ( strpos( $client_ip, self::_cut_ip( $ip_by_host ) ) === false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check for unwanted languages
	 *
	 * @since   2.0
	 * @change  2.6.6
	 * @change  2.8.2
	 *
	 * @param  string $comment_content Content of the comment.
	 *
	 * @return boolean TRUE if it is spam.
	 */
	private static function _is_lang_spam( $comment_content ) {
		$allowed_lang = (array) self::get_option( 'translate_lang' );

		$comment_text = wp_strip_all_tags( $comment_content );

		if ( empty( $allowed_lang ) || empty( $comment_text ) ) {
			return false;
		}

		/**
		 * Filters the detected language. With this filter, other detection methods can skip in and detect the language.
		 *
		 * @since 2.8.2
		 *
		 * @param null   $detected_lang The detected language.
		 * @param string $comment_text  The text, to detect the language.
		 *
		 * @return null|string The detected language or null.
		 */
		$detected_lang = apply_filters( 'antispam_bee_detected_lang', null, $comment_text );
		if ( null !== $detected_lang ) {
			return ! in_array( $detected_lang, $allowed_lang, true );
		}

		$word_count = 0;
		$text       = trim( preg_replace( "/[\n\r\t ]+/", ' ', $comment_text ), ' ' );

		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		if ( strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' ) === 0 && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
			preg_match_all( '/./u', $text, $words_array );
			if ( isset( $words_array[0] ) ) {
				$word_count = count( $words_array[0] );
			}
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY );
			$word_count  = count( $words_array );
		}

		if ( $word_count < 10 ) {
			return false;
		}

		$response = wp_safe_remote_post(
			'https://api.pluginkollektiv.org/language/v1/',
			array( 'body' => wp_json_encode( array( 'body' => $comment_text ) ) )
		);

		if ( is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$detected_lang = wp_remote_retrieve_body( $response );
		if ( ! $detected_lang ) {
			return false;
		}

		$detected_lang = json_decode( $detected_lang );
		if ( ! $detected_lang || ! isset( $detected_lang->code ) ) {
			return false;
		}

		return ! in_array( self::_map_lang_code( $detected_lang->code ), $allowed_lang, true );
	}

	/**
	 * Map franc language codes
	 *
	 * @since   2.9.0
	 *
	 * @param  string $franc_code The franc code, received from the service.
	 *
	 * @return string             Mapped ISO code
	 */
	private static function _map_lang_code( $franc_code ) {
		$codes = array(
			'zha' => 'za',
			'zho' => 'zh',
			'zul' => 'zu',
			'yid' => 'yi',
			'yor' => 'yo',
			'xho' => 'xh',
			'wln' => 'wa',
			'wol' => 'wo',
			'ven' => 've',
			'vie' => 'vi',
			'vol' => 'vo',
			'uig' => 'ug',
			'ukr' => 'uk',
			'urd' => 'ur',
			'uzb' => 'uz',
			'tah' => 'ty',
			'tam' => 'ta',
			'tat' => 'tt',
			'tel' => 'te',
			'tgk' => 'tg',
			'tgl' => 'tl',
			'tha' => 'th',
			'tir' => 'ti',
			'ton' => 'to',
			'tsn' => 'tn',
			'tso' => 'ts',
			'tuk' => 'tk',
			'tur' => 'tr',
			'twi' => 'tw',
			'sag' => 'sg',
			'san' => 'sa',
			'sin' => 'si',
			'slk' => 'sk',
			'slv' => 'sl',
			'sme' => 'se',
			'smo' => 'sm',
			'sna' => 'sn',
			'snd' => 'sd',
			'som' => 'so',
			'sot' => 'st',
			'spa' => 'es',
			'sqi' => 'sq',
			'srd' => 'sc',
			'srp' => 'sr',
			'ssw' => 'ss',
			'sun' => 'su',
			'swa' => 'sw',
			'swe' => 'sv',
			'roh' => 'rm',
			'ron' => 'ro',
			'run' => 'rn',
			'rus' => 'ru',
			'que' => 'qu',
			'pan' => 'pa',
			'pli' => 'pi',
			'pol' => 'pl',
			'por' => 'pt',
			'pus' => 'ps',
			'oci' => 'oc',
			'oji' => 'oj',
			'ori' => 'or',
			'orm' => 'om',
			'oss' => 'os',
			'nau' => 'na',
			'nav' => 'nv',
			'nbl' => 'nr',
			'nde' => 'nd',
			'ndo' => 'ng',
			'nep' => 'ne',
			'nld' => 'nl',
			'nno' => 'nn',
			'nob' => 'nb',
			'nor' => 'no',
			'nya' => 'ny',
			'mah' => 'mh',
			'mal' => 'ml',
			'mar' => 'mr',
			'mkd' => 'mk',
			'mlg' => 'mg',
			'mlt' => 'mt',
			'mon' => 'mn',
			'mri' => 'mi',
			'msa' => 'ms',
			'mya' => 'my',
			'lao' => 'lo',
			'lat' => 'la',
			'lav' => 'lv',
			'lim' => 'li',
			'lin' => 'ln',
			'lit' => 'lt',
			'ltz' => 'lb',
			'lub' => 'lu',
			'lug' => 'lg',
			'kal' => 'kl',
			'kan' => 'kn',
			'kas' => 'ks',
			'kat' => 'ka',
			'kau' => 'kr',
			'kaz' => 'kk',
			'khm' => 'km',
			'kik' => 'ki',
			'kin' => 'rw',
			'kir' => 'ky',
			'kom' => 'kv',
			'kon' => 'kg',
			'kor' => 'ko',
			'kua' => 'kj',
			'kur' => 'ku',
			'jav' => 'jv',
			'jpn' => 'ja',
			'ibo' => 'ig',
			'ido' => 'io',
			'iii' => 'ii',
			'iku' => 'iu',
			'ile' => 'ie',
			'ina' => 'ia',
			'ind' => 'id',
			'ipk' => 'ik',
			'isl' => 'is',
			'ita' => 'it',
			'hat' => 'ht',
			'hau' => 'ha',
			'hbs' => 'sh',
			'heb' => 'he',
			'her' => 'hz',
			'hin' => 'hi',
			'hmo' => 'ho',
			'hrv' => 'hr',
			'hun' => 'hu',
			'hye' => 'hy',
			'gla' => 'gd',
			'gle' => 'ga',
			'glg' => 'gl',
			'glv' => 'gv',
			'grn' => 'gn',
			'guj' => 'gu',
			'fao' => 'fo',
			'fas' => 'fa',
			'fij' => 'fj',
			'fin' => 'fi',
			'fra' => 'fr',
			'fry' => 'fy',
			'ful' => 'ff',
			'ell' => 'el',
			'eng' => 'en',
			'epo' => 'eo',
			'est' => 'et',
			'eus' => 'eu',
			'ewe' => 'ee',
			'dan' => 'da',
			'deu' => 'de',
			'div' => 'dv',
			'dzo' => 'dz',
			'cat' => 'ca',
			'ces' => 'cs',
			'cha' => 'ch',
			'che' => 'ce',
			'chu' => 'cu',
			'chv' => 'cv',
			'cor' => 'kw',
			'cos' => 'co',
			'cre' => 'cr',
			'cym' => 'cy',
			'bak' => 'ba',
			'bam' => 'bm',
			'bel' => 'be',
			'ben' => 'bn',
			'bis' => 'bi',
			'bod' => 'bo',
			'bos' => 'bs',
			'bre' => 'br',
			'bul' => 'bg',
			'aar' => 'aa',
			'abk' => 'ab',
			'afr' => 'af',
			'aka' => 'ak',
			'amh' => 'am',
			'ara' => 'ar',
			'arg' => 'an',
			'asm' => 'as',
			'ava' => 'av',
			'ave' => 'ae',
			'aym' => 'ay',
			'aze' => 'az',
			'nds' => 'de',
		);

		if ( array_key_exists( $franc_code, $codes ) ) {
			return $codes[ $franc_code ];
		}

		return $franc_code;
	}

	/**
	 * Trim IP addresses
	 *
	 * @since   0.1
	 * @change  2.5.1
	 *
	 * @param   string  $ip       Original IP.
	 * @param   boolean $cut_end  Shortening the end.
	 * @return  string            Shortened IP.
	 */
	private static function _cut_ip( $ip, $cut_end = true ) {
		$separator = ( self::_is_ipv4( $ip ) ? '.' : ':' );

		return str_replace(
			( $cut_end ? strrchr( $ip, $separator ) : strstr( $ip, $separator ) ),
			'',
			$ip
		);
	}


	/**
	 * Anonymize the IP addresses
	 *
	 * @since   2.5.1
	 * @change  2.5.1
	 *
	 * @param   string $ip Original IP.
	 * @return  string     Anonymous IP.
	 */
	private static function _anonymize_ip( $ip ) {
		if ( self::_is_ipv4( $ip ) ) {
			return self::_cut_ip( $ip ) . '.0';
		}

		return self::_cut_ip( $ip, false ) . ':0:0:0:0:0:0:0';
	}


	/**
	 * Rotates the IP address
	 *
	 * @since   2.4.5
	 * @change  2.4.5
	 *
	 * @param   string $ip  IP address.
	 * @return  string      Turned IP address.
	 */
	private static function _reverse_ip( $ip ) {
		return implode(
			'.',
			array_reverse(
				explode(
					'.',
					$ip
				)
			)
		);
	}


	/**
	 * Check for an IPv4 address
	 *
	 * @since   2.4
	 * @change  2.6.4
	 *
	 * @param   string $ip  IP to validate.
	 * @return  integer       TRUE if IPv4.
	 */
	private static function _is_ipv4( $ip ) {
		if ( function_exists( 'filter_var' ) ) {
			return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
		} else {
			return preg_match( '/^\d{1,3}(\.\d{1,3}){3,3}$/', $ip );
		}
	}


	/**
	 * Check for an IPv6 address
	 *
	 * @since   2.6.2
	 * @change  2.6.4
	 *
	 * @param   string $ip  IP to validate.
	 * @return  boolean       TRUE if IPv6.
	 */
	private static function _is_ipv6( $ip ) {
		if ( function_exists( 'filter_var' ) ) {
			return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
		} else {
			return ! self::_is_ipv4( $ip );
		}
	}


	/**
	 * Testing on mobile devices
	 *
	 * @since   0.1
	 * @change  2.4
	 *
	 * @return  boolean  TRUE if "wptouch" is active
	 */
	private static function _is_mobile() {
		return strpos( get_template_directory(), 'wptouch' );
	}



	/*
	*   ############################
	*   #####  SPAM-TREATMENT  #####
	*   ############################
	*/

	/**
	 * Execution of the delete/marking process
	 *
	 * @since   0.1
	 * @change  2.6.0
	 *
	 * @param   array   $comment  Untreated commentary data.
	 * @param   string  $reason   Reason for suspicion.
	 * @param   boolean $is_ping  Ping (optional).
	 * @return  array    $comment  Treated commentary data.
	 */
	private static function _handle_spam_request( $comment, $reason, $is_ping = false ) {

		$options = self::get_options();

		$spam_remove = ! $options['flag_spam'];
		$spam_notice = ! $options['no_notice'];

		// Filter settings.
		$ignore_filter = $options['ignore_filter'];
		$ignore_type   = $options['ignore_type'];
		$ignore_reason = in_array( $reason, (array) $options['ignore_reasons'], true );

		// Remember spam.
		self::_update_spam_log( $comment );
		self::_update_spam_count();
		self::_update_daily_stats();

		// Delete spam.
		if ( $spam_remove ) {
			self::_go_in_peace();
		}

		if ( $ignore_filter && ( ( 1 === (int) $ignore_type && $is_ping ) || ( 2 === (int) $ignore_type && ! $is_ping ) ) ) {
			self::_go_in_peace();
		}

		// Spam reason.
		if ( $ignore_reason ) {
			self::_go_in_peace();
		}
		self::$_reason = $reason;

		// Mark spam.
		add_filter(
			'pre_comment_approved',
			array(
				__CLASS__,
				'return_spam',
			)
		);

		// Send e-mail.
		add_action(
			'comment_post',
			array(
				__CLASS__,
				'send_mail_notification',
			)
		);

		// Spam reason as comment meta.
		if ( $spam_notice ) {
			add_action(
				'comment_post',
				array(
					__CLASS__,
					'add_spam_reason_to_comment',
				)
			);
		}

		return $comment;
	}


	/**
	 * Logfile with detected spam
	 *
	 * @since   2.5.7
	 * @change  2.6.1
	 *
	 * @param   array $comment Array with commentary data.
	 * @return  mixed        FALSE in case of error
	 */
	private static function _update_spam_log( $comment ) {
		if ( ! defined( 'ANTISPAM_BEE_LOG_FILE' ) || ! ANTISPAM_BEE_LOG_FILE || ! is_writable( ANTISPAM_BEE_LOG_FILE ) || validate_file( ANTISPAM_BEE_LOG_FILE ) === 1 ) {
			return false;
		}

		$entry = sprintf(
			'%s comment for post=%d from host=%s marked as spam%s',
			current_time( 'mysql' ),
			$comment['comment_post_ID'],
			$comment['comment_author_IP'],
			PHP_EOL
		);

		file_put_contents(
			ANTISPAM_BEE_LOG_FILE,
			$entry,
			FILE_APPEND | LOCK_EX
		);
	}


	/**
	 * Sends the 403 header and terminates the connection
	 *
	 * @since   2.5.6
	 * @change  2.5.6
	 */
	private static function _go_in_peace() {
		status_header( 403 );
		die( 'Spam deleted.' );
	}


	/**
	 * Return real client IP
	 *
	 * @since   2.6.1
	 * @change  2.6.1
	 *
	 * @return  mixed  $ip  Client IP
	 */
	public static function get_client_ip() {
		// phpcs:disable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
		// Sanitization of $ip takes place further down.
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_FORWARDED'] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
		} else {
			return '';
		}
		// phpcs:enable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized

		if ( strpos( $ip, ',' ) !== false ) {
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );
		}

		if ( function_exists( 'filter_var' ) ) {
			return filter_var(
				$ip,
				FILTER_VALIDATE_IP
			);
		}

		return preg_replace(
			'/[^0-9a-f:\., ]/si',
			'',
			$ip
		);
	}

	/**
	 * Add spam reason as comment data
	 *
	 * @since   2.6.0
	 * @change  2.6.0
	 *
	 * @param   integer $comment_id  Comment ID.
	 */
	public static function add_spam_reason_to_comment( $comment_id ) {
		add_comment_meta(
			$comment_id,
			'antispam_bee_reason',
			self::$_reason
		);
	}

	/**
	 * Saves the IP address.
	 *
	 * @param int $comment_id The ID of the comment.
	 */
	public static function save_ip_hash( $comment_id ) {
		$hashed_ip = self::hash_ip( self::get_client_ip() );
		add_comment_meta(
			$comment_id,
			'antispam_bee_iphash',
			$hashed_ip
		);
	}

	/**
	 * Hashes an IP address
	 *
	 * @param string $ip The IP address to hash.
	 *
	 * @return string
	 */
	public static function hash_ip( $ip ) {
		return wp_hash_password( $ip );
	}


	/**
	 * Delete spam reason as comment data
	 *
	 * @since   2.6.0
	 * @change  2.6.0
	 *
	 * @param   integer $comment_id  Comment ID.
	 */
	public static function delete_spam_reason_by_comment( $comment_id ) {
		delete_comment_meta(
			$comment_id,
			'antispam_bee_reason'
		);
	}

	/**
	 * Get the current post ID.
	 *
	 * @since   2.7.1
	 */
	public static function populate_post_id() {

		if ( null === self::$_current_post_id ) {
			self::$_current_post_id = get_the_ID();
		}
	}


	/**
	 * Send notification via e-mail
	 *
	 * @since   0.1
	 * @change  2.5.7
	 *
	 * @hook    string  antispam_bee_notification_subject  Custom subject for notification mails
	 *
	 * @param   int $id  ID of the comment.
	 * @return  int  $id  ID of the comment.
	 */
	public static function send_mail_notification( $id ) {
		$options = self::get_options();

		if ( ! $options['email_notify'] ) {
			return $id;
		}

		$comment = get_comment( $id, ARRAY_A );

		if ( empty( $comment ) ) {
			return $id;
		}

		$post = get_post( $comment['comment_post_ID'] );
		if ( ! $post ) {
			return $id;
		}

		self::load_plugin_lang();

		$subject = sprintf(
			'[%s] %s',
			stripslashes_deep(
				html_entity_decode(
					get_bloginfo( 'name' ),
					ENT_QUOTES
				)
			),
			esc_html__( 'Comment marked as spam', 'antispam-bee' )
		);

		// Content.
		$content = strip_tags( stripslashes( $comment['comment_content'] ) );
		if ( ! $content ) {
			$content = sprintf(
				'-- %s --',
				esc_html__( 'Content removed by Antispam Bee', 'antispam-bee' )
			);
		}

		// Body.
		$body = sprintf(
			"%s \"%s\"\r\n\r\n",
			esc_html__( 'New spam comment on your post', 'antispam-bee' ),
			strip_tags( $post->post_title )
		) . sprintf(
			"%s: %s\r\n",
			esc_html__( 'Author', 'antispam-bee' ),
			( empty( $comment['comment_author'] ) ? '' : strip_tags( $comment['comment_author'] ) )
		) . sprintf(
			"URL: %s\r\n",
			// empty check exists.
			esc_url( $comment['comment_author_url'] )
		) . sprintf(
			"%s: %s\r\n",
			esc_html__( 'Type', 'antispam-bee' ),
			esc_html( ( empty( $comment['comment_type'] ) ? __( 'Comment', 'antispam-bee' ) : __( 'Trackback', 'antispam-bee' ) ) )
		) . sprintf(
			"Whois: http://whois.arin.net/rest/ip/%s\r\n",
			$comment['comment_author_IP']
		) . sprintf(
			"%s: %s\r\n\r\n",
			esc_html__( 'Spam Reason', 'antispam-bee' ),
			esc_html( self::$defaults['reasons'][ self::$_reason ] )
		) . sprintf(
			"%s\r\n\r\n\r\n",
			$content
		) . (
			EMPTY_TRASH_DAYS ? (
				sprintf(
					"%s: %s\r\n",
					esc_html__( 'Trash it', 'antispam-bee' ),
					admin_url( 'comment.php?action=trash&c=' . $id )
				)
			) : (
				sprintf(
					"%s: %s\r\n",
					esc_html__( 'Delete it', 'antispam-bee' ),
					admin_url( 'comment.php?action=delete&c=' . $id )
				)
			)
		) . sprintf(
			"%s: %s\r\n",
			esc_html__( 'Approve it', 'antispam-bee' ),
			admin_url( 'comment.php?action=approve&c=' . $id )
		) . sprintf(
			"%s: %s\r\n\r\n",
			esc_html__( 'Spam list', 'antispam-bee' ),
			admin_url( 'edit-comments.php?comment_status=spam' )
		) . sprintf(
			"%s\r\n%s\r\n",
			esc_html__( 'Notify message by Antispam Bee', 'antispam-bee' ),
			esc_html__( 'http://antispambee.com', 'antispam-bee' )
		);

		wp_mail(
			/**
			 * Filters the recipients of the spam notification.
			 *
			 * @param array The recipients array.
			 */
			apply_filters(
				'antispam_bee_notification_recipients',
				array( get_bloginfo( 'admin_email' ) )
			),
			/**
			 * Filters the subject of the spam notification.
			 *
			 * @param string $subject subject line.
			 */
			apply_filters(
				'antispam_bee_notification_subject',
				$subject
			),
			$body
		);

		return $id;
	}



	/*
	*   ############################
	*   #######  STATISTICS  #######
	*   ############################
	*/

	/**
	 * Return the number of spam comments
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	private static function _get_spam_count() {
		// Init.
		$count = self::get_option( 'spam_count' );

		// Fire.
		return ( get_locale() === 'de_DE' ? number_format( $count, 0, '', '.' ) : number_format_i18n( $count ) );
	}


	/**
	 * Output the number of spam comments
	 *
	 * @since   0.1
	 * @change  2.4
	 */
	public static function the_spam_count() {
		echo esc_html( self::_get_spam_count() );
	}


	/**
	 * Update the number of spam comments
	 *
	 * @since   0.1
	 * @change  2.6.1
	 */
	private static function _update_spam_count() {
		// Skip if not enabled.
		if ( ! self::get_option( 'dashboard_count' ) ) {
			return;
		}

		self::_update_option(
			'spam_count',
			intval( self::get_option( 'spam_count' ) + 1 )
		);
	}

	/**
	 * Update statistics
	 *
	 * @since   1.9
	 * @change  2.6.1
	 */
	private static function _update_daily_stats() {
		// Skip if not enabled.
		if ( ! self::get_option( 'dashboard_chart' ) ) {
			return;
		}

		// Init.
		$stats = (array) self::get_option( 'daily_stats' );
		$today = (int) strtotime( 'today' );

		// Count up.
		if ( array_key_exists( $today, $stats ) ) {
			$stats[ $today ] ++;
		} else {
			$stats[ $today ] = 1;
		}

		// Sort.
		krsort( $stats, SORT_NUMERIC );

		// Save.
		self::_update_option(
			'daily_stats',
			array_slice( $stats, 0, 31, true )
		);
	}

	/**
	 * Returns the secret of a post used in the textarea name attribute.
	 *
	 * @param int $post_id The Post ID.
	 *
	 * @return string
	 */
	public static function get_secret_name_for_post( $post_id ) {

		if ( self::get_option( 'always_allowed' ) ) {
			$secret = substr( sha1( md5( 'comment-id' . self::$_salt ) ), 0, 10 );
		} else {
			$secret = substr( sha1( md5( 'comment-id' . self::$_salt . (int) $post_id ) ), 0, 10 );
		}

		$secret = self::ensure_secret_starts_with_letter( $secret );

		/**
		 * Filters the secret for a post, which is used in the textarea name attribute.
		 *
		 * @param string $secret The secret.
		 * @param int    $post_id The post ID.
		 * @param bool   $always_allowed Whether the comment form is used outside of the single post view or not.
		 */
		return apply_filters(
			'ab_get_secret_name_for_post',
			$secret,
			(int) $post_id,
			(bool) self::get_option( 'always_allowed' )
		);

	}

	/**
	 * Returns the secret of a post used in the textarea id attribute.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public static function get_secret_id_for_post( $post_id ) {

		if ( self::get_option( 'always_allowed' ) ) {
			$secret = substr( sha1( md5( 'comment-id' . self::$_salt ) ), 0, 10 );
		} else {
			$secret = substr( sha1( md5( 'comment-id' . self::$_salt . (int) $post_id ) ), 0, 10 );
		}

		$secret = self::ensure_secret_starts_with_letter( $secret );

		/**
		 * Filters the secret for a post, which is used in the textarea id attribute.
		 *
		 * @param string $secret The secret.
		 * @param int    $post_id The post ID.
		 * @param bool   $always_allowed Whether the comment form is used outside of the single post view or not.
		 */
		return apply_filters(
			'ab_get_secret_id_for_post',
			$secret,
			(int) $post_id,
			(bool) self::get_option( 'always_allowed' )
		);
	}

	/**
	 * Ensures that the secret starts with a letter.
	 *
	 * @param string $secret The secret.
	 *
	 * @return string
	 */
	public static function ensure_secret_starts_with_letter( $secret ) {

		$first_char = substr( $secret, 0, 1 );
		if ( is_numeric( $first_char ) ) {
			return chr( $first_char + 97 ) . substr( $secret, 1 );
		} else {
			return $secret;
		}
	}

	/**
	 * Returns 'spam'
	 *
	 * @since 2.7.3
	 *
	 * @return string
	 */
	public static function return_spam() {

		return 'spam';
	}

	/**
	 * A wrapper around wp_parse_url().
	 *
	 * @since 2.8.2
	 *
	 * @param string $url The URL to parse.
	 * @param string $component The component to get back.
	 *
	 * @return string
	 */
	private static function parse_url( $url, $component = 'host' ) {

		$parts = wp_parse_url( $url );
		return ( is_array( $parts ) && isset( $parts[ $component ] ) ) ? $parts[ $component ] : '';
	}

	/**
	 * Updates the database structure if necessary
	 */
	public static function update_database() {
		if ( self::db_version_is_current() ) {
			return;
		}

		global $wpdb;

		/**
		 * In Version 2.9 the IP of the commenter was saved as a hash. We reverted this solution.
		 * Therefore, we need to delete this unused data.
		 */
		//phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
		$sql = 'delete from `' . $wpdb->commentmeta . '` where `meta_key` IN ("antispam_bee_iphash")';
		$wpdb->query( $sql );
		//phpcs:enable WordPress.WP.PreparedSQL.NotPrepared

		update_option( 'antispambee_db_version', self::$db_version );
	}

	/**
	 * Whether the database structure is up to date.
	 *
	 * @return bool
	 */
	private static function db_version_is_current() {

		$current_version = absint( get_option( 'antispambee_db_version', 0 ) );
		return $current_version === self::$db_version;

	}
}


// Fire.
add_action(
	'plugins_loaded',
	array(
		'Antispam_Bee',
		'init',
	)
);

// Activation.
register_activation_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'activate',
	)
);

// Deactivation.
register_deactivation_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'deactivate',
	)
);

// Uninstall.
register_uninstall_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'uninstall',
	)
);
