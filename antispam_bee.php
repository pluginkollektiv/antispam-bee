<?php
/*
* Plugin Name: Antispam Bee
* Description: Easy and extremely productive spam-fighting plugin with many sophisticated solutions. Includes privacy hints and protection against trackback spam.
* Author:      pluginkollektiv
* Author URI:  http://pluginkollektiv.org
* Plugin URI:  https://wordpress.org/plugins/antispam-bee/
* Text Domain: antispam-bee
* Domain Path: /lang
* License:     GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
* Version:     2.7.1
*/

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


	// Init
	public static $defaults;
	private static $_base;
	private static $_salt;
	private static $_reason;
	private static $_current_post_id;


	/**
	* "Constructor" of the class
	*
	* @since   0.1
	* @change  2.6.4
	*/

  	public static function init()
  	{
  		// Delete spam reason
  		add_action(
  			'unspam_comment',
  			array(
  				__CLASS__,
  				'delete_spam_reason_by_comment'
  			)
  		);

		// AJAX & Co.
		if ( (defined('DOING_AJAX') && DOING_AJAX) or (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) {
			return;
		}

		// Initialization
		self::_init_internal_vars();

		// Cronjob
		if ( defined('DOING_CRON') ) {
			add_action(
				'antispam_bee_daily_cronjob',
				array(
					__CLASS__,
					'start_daily_cronjob'
				)
			);

		// Admin
		} elseif ( is_admin() ) {
			// Menu
			add_action(
				'admin_menu',
				array(
					__CLASS__,
					'add_sidebar_menu'
				)
			);

			// Dashboard
			if ( self::_current_page('dashboard') ) {
				add_action(
					'init',
					array(
						__CLASS__,
						'load_plugin_lang'
					)
				);
				add_filter(
					'dashboard_glance_items',
					array(
						__CLASS__,
						'add_dashboard_count'
					)
				);
				add_action(
					'wp_dashboard_setup',
					array(
						__CLASS__,
						'add_dashboard_chart'
					)
				);

			// Plugins
			} else if ( self::_current_page('plugins') ) {
				add_action(
					'init',
					array(
						__CLASS__,
						'load_plugin_lang'
					)
				);
				add_filter(
					'plugin_row_meta',
					array(
						__CLASS__,
						'init_row_meta'
					),
					10,
					2
				);
				add_filter(
					'plugin_action_links_' .self::$_base,
					array(
						__CLASS__,
						'init_action_links'
					)
				);

			// Options
			} else if ( self::_current_page('options') ) {
				add_action(
					'admin_init',
					array(
						__CLASS__,
						'load_plugin_lang'
					)
				);
				add_action(
					'admin_init',
					array(
						__CLASS__,
						'init_plugin_sources'
					)
				);

			} else if ( self::_current_page('admin-post') ) {
				require_once( dirname(__FILE__). '/inc/gui.class.php' );

				add_action(
					'admin_post_ab_save_changes',
					array(
						'Antispam_Bee_GUI',
						'save_changes'
					)
				);

			} else if ( self::_current_page('edit-comments') ) {
				if ( ! empty($_GET['comment_status']) && $_GET['comment_status'] === 'spam' && ! self::get_option('no_notice') ) {
					// Include file
					require_once( dirname(__FILE__). '/inc/columns.class.php' );

					// Load textdomain
					self::load_plugin_lang();

					// Add plugin columns
					add_filter(
						'manage_edit-comments_columns',
						array(
							'Antispam_Bee_Columns',
							'register_plugin_columns'
						)
					);
					add_filter(
						'manage_comments_custom_column',
						array(
							'Antispam_Bee_Columns',
							'print_plugin_column'
						),
						10,
						2
					);
					add_filter(
					    'admin_print_styles-edit-comments.php',
					    array(
					    	'Antispam_Bee_Columns',
					    	'print_column_styles'
					    )
					);

					add_filter(
						'manage_edit-comments_sortable_columns',
						array(
							'Antispam_Bee_Columns',
							'register_sortable_columns'
						)
					);
					add_action(
						'pre_get_posts',
						array(
							'Antispam_Bee_Columns',
							'set_orderby_query'
						)
					);
				}
			}

		// Frontend
		} else {
			add_action(
				'wp',
				array(
					__CLASS__,
					'populate_post_id'
				)
			);

			add_action(
				'template_redirect',
				array(
					__CLASS__,
					'prepare_comment_field'
				)
			);
			add_action(
				'init',
				array(
					__CLASS__,
					'precheck_incoming_request'
				)
			);
			add_action(
				'preprocess_comment',
				array(
					__CLASS__,
					'handle_incoming_request'
				),
				1
			);
			add_action(
				'antispam_bee_count',
				array(
					__CLASS__,
					'the_spam_count'
				)
			);
		}
	}



/*
*	############################
*	########  INSTALL  #########
*	############################
*/

	/**
	* Action during the activation of the Plugins 
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function activate()
	{
		// Apply Option
		add_option(
			'antispam_bee',
			array(),
			'',
			'no'
		);

		// Activate Cron
		if ( self::get_option('cronjob_enable') ) {
			self::init_scheduled_hook();
		}
	}


	/**
	* Action to deactivate the plugin
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function deactivate()
	{
		self::clear_scheduled_hook();
	}


	/**
	* Action deleting the plugin
	*
	* @since   2.4
	* @change  2.4
	*/

	public static function uninstall()
	{
		// Global
		global $wpdb;

		// Remove settings
		delete_option('antispam_bee');

		// Clean DB
		$wpdb->query("OPTIMIZE TABLE `" .$wpdb->options. "`");
	}



/*
*	############################
*	########  INTERNAL  ########
*	############################
*/

	/**
	* Initialization of the internal variables
	*
	* @since   2.4
	* @change  2.7.0
	*/

	private static function _init_internal_vars()
	{
		self::$_base   = plugin_basename(__FILE__);

		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : ABSPATH;
		self::$_salt = substr( sha1( $salt ), 0, 10 );

		self::$defaults = array(
			'options' => array(
				// General
				'advanced_check' 	=> 1,
				'regexp_check'		=> 1,
				'spam_ip' 			=> 1,
				'already_commented'	=> 1,
				'gravatar_check'	=> 0,
				'time_check'		=> 0,
				'ignore_pings' 		=> 0,
				'always_allowed' 	=> 0,

				'dashboard_chart' 	=> 0,
				'dashboard_count' 	=> 0,

				// Filter
				'country_code' 		=> 0,
				'country_black'		=> '',
				'country_white'		=> '',

				'translate_api' 	=> 0,
				'translate_lang'	=> '',

				'dnsbl_check'		=> 0,
				'bbcode_check'		=> 1,

				// Advanced
				'flag_spam' 		=> 1,
				'email_notify' 		=> 1,
				'no_notice' 		=> 0,
				'cronjob_enable' 	=> 0,
				'cronjob_interval'	=> 0,

				'ignore_filter' 	=> 0,
				'ignore_type' 		=> 0,

				'reasons_enable'	=> 0,
				'ignore_reasons'	=> array(),
			),
			'reasons' => array(
				'css'		=> esc_attr__( 'CSS Hack', 'antispam-bee' ),
				'time'		=> esc_attr__( 'Comment time', 'antispam-bee' ),
				'empty'		=> esc_attr__( 'Empty Data', 'antispam-bee' ),
				'server'	=> esc_attr__( 'Fake IP', 'antispam-bee' ),
				'localdb'	=> esc_attr__( 'Local DB Spam', 'antispam-bee' ),
				'country'	=> esc_attr__( 'Country Check', 'antispam-bee' ),
				'dnsbl'		=> esc_attr__( 'Public Antispam DB', 'antispam-bee' ),
				'bbcode'	=> esc_attr__( 'BBCode', 'antispam-bee' ),
				'lang'		=> esc_attr__( 'Comment Language', 'antispam-bee' ),
				'regexp'	=> esc_attr__( 'Regular Expression', 'antispam-bee' ),
			)
		);
	}


	/**
	* Check and return an array key
	*
	* @since   2.4.2
	* @change  2.4.2
	*
	* @param   array   $array  Array with values
	* @param   string  $key    Name of the key
	* @return  mixed           Value of the requested key
	*/

	public static function get_key($array, $key)
	{
		if ( empty($array) or empty($key) or empty($array[$key]) ) {
			return null;
		}

		return $array[$key];
	}


	/**
	* Localization of the admin pages
	*
	* @since   0.1
	* @change  2.4
	*
	* @param   string   $page  Mark the page
	* @return  boolean         TRUE on success
	*/

	private static function _current_page($page)
	{
		switch ($page) {
			case 'dashboard':
				return ( empty($GLOBALS['pagenow']) or ( !empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'index.php' ) );

			case 'options':
				return ( !empty($_GET['page']) && $_GET['page'] == 'antispam_bee' );

			case 'plugins':
				return ( !empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'plugins.php' );

			case 'admin-post':
				return ( !empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'admin-post.php' );

			case 'edit-comments':
				return ( !empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'edit-comments.php' );

			default:
				return false;
		}
	}


	/**
	* Integration of the localization file
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function load_plugin_lang()
	{
		load_plugin_textdomain(
			'antispam-bee',
			false,
			'antispam-bee/lang'
		);
	}


	/**
	* Add the link to the settings
	*
	* @since   1.1
	* @change  1.1
	*/

	public static function init_action_links($data)
	{
		// Rights?
		if ( ! current_user_can('manage_options') ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'page' => 'antispam_bee'
						),
						admin_url('options-general.php')
					),
					esc_attr__('Settings', 'antispam-bee')
				)
			)
		);
	}


	/**
	* Meta links of the plugin
	*
	* @since   0.1
	* @change  2.6.2
	*
	* @param   array   $input  Existing links
	* @param   string  $file   Current page
	* @return  array   $data   Modified links
	*/

	public static function init_row_meta($input, $file)
	{
		// Rights
		if ( $file != self::$_base ) {
			return $input;
		}

		return array_merge(
			$input,
			array(
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8CH5FPR88QYML" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'antispam-bee' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/antispam-bee" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'antispam-bee' ) . '</a>',
			)
		);
	}



/*
*	############################
*	#######  RESOURCES  ########
*	############################
*/

	/**
	* Registration of resources (CSS & JS)
	*
	* @since   1.6
	* @change  2.4.5
	*/

	public static function init_plugin_sources()
	{
		// Read information
		$plugin = get_plugin_data(__FILE__);

		// Integrate JS
		wp_register_script(
			'ab_script',
			plugins_url('js/scripts.min.js', __FILE__),
			array('jquery'),
			$plugin['Version']
		);

		// Integrate CSS
		wp_register_style(
			'ab_style',
			plugins_url('css/styles.min.css', __FILE__),
			array(),
			$plugin['Version']
		);
	}


	/**
	* Initialization of the option page
	*
	* @since   0.1
	* @change  2.4.3
	*/

	public static function add_sidebar_menu()
	{
		// Create menu
		$page = add_options_page(
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam_bee',
			array(
				'Antispam_Bee_GUI',
				'options_page'
			)
		);

		// Integrate JS
		add_action(
			'admin_print_scripts-' . $page,
			array(
				__CLASS__,
				'add_options_script'
			)
		);

		// Integrate CSS
		add_action(
			'admin_print_styles-' . $page,
			array(
				__CLASS__,
				'add_options_style'
			)
		);

		// Load PHP
		add_action(
			'load-' .$page,
			array(
				__CLASS__,
				'init_options_page'
			)
		);
	}


	/**
	* Initialization of JavaScript
	*
	* @since   1.6
	* @change  2.4
	*/

	public static function add_options_script()
	{
		wp_enqueue_script('ab_script');
	}


	/**
	* Initialization of Stylesheets
	*
	* @since   1.6
	* @change  2.4
	*/

	public static function add_options_style()
	{
		wp_enqueue_style('ab_style');
	}


	/**
	* Integration of the GUI
	*
	* @since   2.4
	* @change  2.4
	*/

	public static function init_options_page()
	{
		require_once( dirname(__FILE__). '/inc/gui.class.php' );
	}



/*
*	############################
*	#######  DASHBOARD  ########
*	############################
*/

	/**
	* Display the spam counter on the dashboard
	*
	* @since   0.1
	* @change  2.6.5
	*
	* @param   array  $items  Initial array with dashboard items
	* @return  array  $items  Merged array with dashboard items
	*/

	public static function add_dashboard_count( $items = array() )
	{
		// Skip
		if ( ! current_user_can('manage_options') OR ! self::get_option('dashboard_count') ) {
		return $items;
		}

        	// Icon styling
        	echo '<style>#dashboard_right_now .ab-count:before {content: "\f117"}</style>';

		// Right now item
		$items[] = sprintf(
			'<a href="%s" class="ab-count">%s %s</a>',
			add_query_arg(
				array(
					'page' => 'antispam_bee'
					),
				admin_url( 'options-general.php' )
			),
			esc_html( self::_get_spam_count() ),
			esc_html__('Blocked', 'antispam-bee')
		);

	return $items;
	}


	/**
	* Initialize the dashboard chart
	*
	* @since   1.9
	* @change  2.5.6
	*/
	public static function add_dashboard_chart()
	{
		// Filter
		if ( ! current_user_can( 'publish_posts' ) || ! self::get_option( 'dashboard_chart' ) ) {
			return;
		}

		// Add Widget
		wp_add_dashboard_widget(
			'ab_widget',
			'Antispam Bee',
			array(
				__CLASS__,
				'show_spam_chart'
			)
		);

		// Load CSS
		add_action(
			'admin_head',
			array(
				__CLASS__,
				'add_dashboard_style'
			)
		);
	}


	/**
	* Print dashboard styles
	*
	* @since   1.9.0
	* @change  2.5.8
	*/

	public static function add_dashboard_style()
	{
		// Get plugin data
		$plugin = get_plugin_data(__FILE__);

		// Register styles
		wp_register_style(
			'ab_chart',
			plugins_url('css/dashboard.min.css', __FILE__),
			array(),
			$plugin['Version']
		);

		// Embed styles
  		wp_print_styles('ab_chart');
	}


	/**
	* Print dashboard scripts
	*
	* @since   1.9.0
	* @change  2.5.8
	*/
	public static function add_dashboard_script() {
		// Get stats
		if ( ! self::get_option('daily_stats') ) {
			return;
		}

		// Get plugin data
		$plugin = get_plugin_data(__FILE__);

		// Embed scripts
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

	public static function show_spam_chart()
	{
		// Get stats
		$items = (array)self::get_option('daily_stats');

		// Emty array?
		if ( empty($items) ) {
			echo sprintf(
				'<div id="ab_chart"><p>%s</p></div>',
				esc_html__('No data available.', 'antispam-bee')
			);

			return;
		}

		// Enqueue scripts.
		self::add_dashboard_script();

		// Sort stats
		ksort($items, SORT_NUMERIC);

		// Start HTML
		$html = "<table id=ab_chart_data>\n";


		// Timestamp table
		$html .= "<tfoot><tr>\n";
		foreach($items as $date => $count) {
			$html .= "<th>" .$date. "</th>\n";
		}
		$html .= "</tr></tfoot>\n";

		// Counter table
		$html .= "<tbody><tr>\n";
		foreach($items as $date => $count) {
			$html .= "<td>" .(int) $count. "</td>\n";
		}
		$html .= "</tr></tbody>\n";


		// HTML end
		$html .= "</table>\n";

		// Print html
		echo '<div id="ab_chart">' .$html. '</div>';
	}



/*
*	############################
*	########  OPTIONS  #########
*	############################
*/

	/**
	* Get all plugin options
	*
	* @since   2.4
	* @change  2.6.1
	*
	* @return  array  $options  Array with option fields
	*/

	public static function get_options()
	{
		if ( ! $options = wp_cache_get('antispam_bee') ) {
			wp_cache_set(
				'antispam_bee',
				$options = get_option('antispam_bee')
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
	* @param   string  $field  Field name
	* @return  mixed           Field value
	*/

	public static function get_option($field)
	{
		// Get all options
		$options = self::get_options();

		return self::get_key($options, $field);
	}


	/**
	* Update single option field
	*
	* @since   0.1
	* @change  2.4
	*
	* @param   string  $field  Field name
	* @param   mixed           Field value
	*/

	private static function _update_option($field, $value)
	{
		self::update_options(
			array(
				$field => $value
			)
		);
	}


	/**
	* Update multiple option fields
	*
	* @since   0.1
	* @change  2.6.1
	*
	* @param   array  $data  Array with plugin option fields
	*/

	public static function update_options($data)
	{
		// Get options
		$options = get_option('antispam_bee');

		// Merge new data
		if ( is_array($options) ) {
			$options = array_merge(
				$options,
				$data
			);
		}
		else {
			$options = $data;
		}

		// Update options
		update_option(
			'antispam_bee',
			$options
		);

		// Refresh cache
		wp_cache_set(
			'antispam_bee',
			$options
		);
	}



/*
*	############################
*	########  CRONJOBS  ########
*	############################
*/

	/**
	* Execution of the daily cronjobs
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function start_daily_cronjob()
	{
		// No Cronjob?
		if ( !self::get_option('cronjob_enable') ) {
			return;
		}

		// Update timestamp
		self::_update_option(
			'cronjob_timestamp',
			time()
		);

		// Delete spam
		self::_delete_old_spam();
	}


	/**
	* Delete old spam comments
	*
	* @since   0.1
	* @change  2.4
	*/

	private static function _delete_old_spam()
	{
		// Number of days
		$days = (int)self::get_option('cronjob_interval');

		// No value?
		if ( empty($days) ) {
			return false;
		}

		// Global
		global $wpdb;

		// Delete comments
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->comments` WHERE `comment_approved` = 'spam' AND SUBDATE(NOW(), %d) > comment_date_gmt",
				$days
			)
		);

		// DB optimization
		$wpdb->query("OPTIMIZE TABLE `$wpdb->comments`");
	}


	/**
	* Initialization of the cronjobs
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function init_scheduled_hook()
	{
		if ( ! wp_next_scheduled('antispam_bee_daily_cronjob') ) {
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

	public static function clear_scheduled_hook()
	{
		if ( wp_next_scheduled('antispam_bee_daily_cronjob') ) {
			wp_clear_scheduled_hook('antispam_bee_daily_cronjob');
		}
	}



/*
*	############################
*	######  SPAM CHECK  ########
*	############################
*/

	/**
	* Check POST values
	*
	* @since   0.1
	* @change  2.6.3
	*/

	public static function precheck_incoming_request()
	{
		// Skip if not a comment request
		if ( is_feed() OR is_trackback() OR empty($_POST) OR self::_is_mobile() ) {
			return;
		}

		// Request params
		$request_uri = self::get_key($_SERVER, 'REQUEST_URI');
		$request_path = parse_url($request_uri, PHP_URL_PATH);

		// Request check
		if ( strpos($request_path, 'wp-comments-post.php') === false ) {
			return;
		}

		$post_id = (int) self::get_key( $_POST, 'comment_post_ID' );
		// Form fields
		$hidden_field = self::get_key( $_POST, 'comment' );
		$plugin_field = self::get_key( $_POST, self::get_secret_name_for_post( $post_id ) );

		// Hidden field check
		if ( empty($hidden_field) && ! empty($plugin_field) ) {
			$_POST['comment'] = $plugin_field;
			unset( $_POST[ self::get_secret_name_for_post( $post_id ) ] );
		} else {
			$_POST['ab_spam__hidden_field'] = 1;
		}
	}


	/**
	* Check incoming requests for spam
	*
	* @since   0.1
	* @change  2.6.3
	*
	* @param   array  $comment  Untreated comment
	* @return  array  $comment  Treated comment
	*/

	public static function handle_incoming_request($comment)
	{
		// Add client IP
		$comment['comment_author_IP'] = self::get_client_ip();

		// Hook client IP
		add_filter(
			'pre_comment_user_ip',
			array(
				__CLASS__,
				'get_client_ip'
			),
			1
		);

		// Request params
		$request_uri = self::get_key($_SERVER, 'REQUEST_URI');
		$request_path = parse_url($request_uri, PHP_URL_PATH);

		// Empty path?
		if ( empty($request_path) ) {
			return self::_handle_spam_request(
				$comment,
				'empty'
			);
		}

		// Defaults
		$ping = array(
			'types'   => array('pingback', 'trackback', 'pings'),
			'allowed' => !self::get_option('ignore_pings')
		);

		// Is a comment
		if ( strpos($request_path, 'wp-comments-post.php') !== false && ! empty($_POST) ) {
			// Verify request
			$status = self::_verify_comment_request($comment);

			// Treat the request as spam
			if ( ! empty($status['reason']) ) {
				return self::_handle_spam_request(
					$comment,
					$status['reason']
				);
			}

		// Is a trackback
		} else if ( in_array(self::get_key($comment, 'comment_type'), $ping['types']) && $ping['allowed'] ) {
			// Verify request
			$status = self::_verify_trackback_request($comment);

			// Treat the request as spam
			if ( ! empty($status['reason']) ) {
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

	public static function prepare_comment_field()
	{
		// Frontend only
		if ( is_feed() or is_trackback() or is_robots() or self::_is_mobile() ) {
			return;
		}

		// Only Posts
		if ( !is_singular() && !self::get_option('always_allowed') ) {
			return;
		}

		// Fire!
		ob_start(
			array(
				'Antispam_Bee',
				'replace_comment_field'
			)
		);
	}


	/**
	* Replaces the comment field
	*
	* @since   2.4
	* @change  2.6.4
	*
	* @param   string  $data  HTML code of the website
	* @return  string         Treated HTML code
	*/

	public static function replace_comment_field($data)
	{
		// Empty?
		if ( empty($data) ) {
			return;
		}

		// Find the comment textarea
		if ( ! preg_match('#<textarea.+?name=["\']comment["\']#s', $data) ) {
			return $data;
		}

		/* Inject HTML */
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
				><\/textarea>                                                       (?# the closing of the textarea )
			)/x',
			array( 'Antispam_Bee', 'replace_comment_field_callback' ),
			$data,
			1
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
		// Build init time field
		if ( self::get_option('time_check') ) {
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
			$output .= 'id="' . self::get_secret_id_for_post( self::$_current_post_id ) . '" ';
			$id_script = '<script type="text/javascript">document.getElementById("comment").setAttribute( "id", "' . esc_js( md5( time() ) ) . '" );document.getElementById("' . esc_js( self::get_secret_id_for_post( self::$_current_post_id ) ) . '").setAttribute( "id", "comment" );</script>';
		}

		$output .= ' name="' . esc_attr( self::get_secret_name_for_post( self::$_current_post_id ) ) . '" ';
		$output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
		$output .= $matches['after'] . '>';
		$output .= '</textarea><textarea id="comment" aria-hidden="true" name="comment" autocomplete="nope" style="clip:rect(1px, 1px, 1px, 1px);position:absolute !important;white-space:nowrap;height:1px;width:1px;overflow:hidden;" tabindex="-1"></textarea>';

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
	* @param   array  $comment  Trackback data
	* @return  array            Array with suspected reason [optional]
	*/

	private static function _verify_trackback_request($comment)
	{
		// Comment values
		$ip = self::get_key($comment, 'comment_author_IP');
		$url = self::get_key($comment, 'comment_author_url');
		$body = self::get_key($comment, 'comment_content');

		// Empty values?
		if ( empty($url) OR empty($body) ) {
			return array(
				'reason' => 'empty'
			);
		}

		// IP?
		if ( empty($ip) ) {
			return array(
				'reason' => 'empty'
			);
		}

		// Options
		$options = self::get_options();

		// BBCode spam
		if ( $options['bbcode_check'] && self::_is_bbcode_spam($body) ) {
			return array(
				'reason' => 'bbcode'
			);
		}

		// IP != Server
		if ( $options['advanced_check'] && self::_is_fake_ip($ip, parse_url($url, PHP_URL_HOST)) ) {
			return array(
				'reason' => 'server'
			);
		}

		// IP in local spam
		if ( $options['spam_ip'] && self::_is_db_spam($ip, $url) ) {
			return array(
				'reason' => 'localdb'
			);
		}

		// DNSBL spam
		if ( $options['dnsbl_check'] && self::_is_dnsbl_spam($ip) ) {
			return array(
				'reason' => 'dnsbl'
			);
		}

		// Check Country Code
		if ( $options['country_code'] && self::_is_country_spam($ip) ) {
			return array(
				'reason' => 'country'
			);
		}

		// Translate API
		if ( $options['translate_api'] && self::_is_lang_spam($body) ) {
			return array(
				'reason' => 'lang'
			);
		}
	}


	/**
	* Check the comment
	*
	* @since   2.4
	* @change  2.7.0
	*
	* @param   array  $comment  Data of the comment
	* @return  array            Array with suspected reason [optional]
	*/

	private static function _verify_comment_request($comment)
	{
		// Comment values
		$ip = self::get_key($comment, 'comment_author_IP');
		$url = self::get_key($comment, 'comment_author_url');
		$body = self::get_key($comment, 'comment_content');
		$email = self::get_key($comment, 'comment_author_email');
		$author = self::get_key($comment, 'comment_author');

		// Empty values?
		if ( empty($body) ) {
			return array(
				'reason' => 'empty'
			);
		}

		// IP?
		if ( empty($ip) ) {
			return array(
				'reason' => 'empty'
			);
		}

		// Empty values?
		if ( get_option('require_name_email') && ( empty($email) OR empty($author) ) ) {
			return array(
				'reason' => 'empty'
			);
		}

		// Options
		$options = self::get_options();

		// Already commented?
		if ( $options['already_commented'] && ! empty($email) && self::_is_approved_email($email) ) {
			return;
		}

		// Check for a Gravatar
		if ( $options['gravatar_check'] && ! empty($email) && self::_has_valid_gravatar($email) ) {
		    return;
		}

		// Bot detected
		if ( ! empty($_POST['ab_spam__hidden_field']) ) {
			return array(
				'reason' => 'css'
			);
		}

		// Action time
		if ( $options['time_check'] && self::_is_shortest_time() ) {
			return array(
				'reason' => 'time'
			);
		}

		// BBCode spam
		if ( $options['bbcode_check'] && self::_is_bbcode_spam($body) ) {
			return array(
				'reason' => 'bbcode'
			);
		}

		// Extended protection
		if ( $options['advanced_check'] && self::_is_fake_ip($ip) ) {
			return array(
				'reason' => 'server'
			);
		}

		// Regex for spam
		if ( $options['regexp_check'] && self::_is_regexp_spam(
			array(
				'ip'	 => $ip,
				'rawurl' => $url,
				'host'	 => parse_url($url, PHP_URL_HOST),
				'body'	 => $body,
				'email'	 => $email,
				'author' => $author
			)
		) ) {
			return array(
				'reason' => 'regexp'
			);
		}

		// IP in local spam
		if ( $options['spam_ip'] && self::_is_db_spam($ip, $url, $email) ) {
			return array(
				'reason' => 'localdb'
			);
		}

		// DNSBL spam
		if ( $options['dnsbl_check'] && self::_is_dnsbl_spam($ip) ) {
			return array(
				'reason' => 'dnsbl'
			);
		}

		// Check Country Code
		if ( $options['country_code'] && self::_is_country_spam($ip) ) {
			return array(
				'reason' => 'country'
			);
		}

		// Translate API
		if ( $options['translate_api'] && self::_is_lang_spam($body) ) {
			return array(
				'reason' => 'lang'
			);
		}
	}


	/**
	* Check for a Gravatar image
	*
	* @since   2.6.5
	* @change  2.6.5
	*
	* @param   string	$email  Input email
	* @return  boolean       	Check status (true = Gravatar available)
	*/

    private static function _has_valid_gravatar($email) {
        $response = wp_safe_remote_get(
            sprintf(
                'https://www.gravatar.com/avatar/%s?d=404',
                md5( strtolower( trim($email) ) )
            )
        );

        if ( is_wp_error($response) ) {
            return null;
        }

        if ( wp_remote_retrieve_response_code($response) === 200 ) {
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

	private static function _is_shortest_time()
	{
		// Comment init time
		if ( ! $init_time = (int)self::get_key($_POST, 'ab_init_time') ) {
			return false;
		}

		// Compare time values
		if ( time() - $init_time < apply_filters('ab_action_time_limit', 5) ) {
			return true;
		}

		return false;
	}


	/**
	* Usage of regexp, also custom
	*
	* @since   2.5.2
	* @change  2.5.6
	*
	* @param   array	$comment  Array with commentary data
	* @return  boolean       	  TRUE for suspicious comment
	*/

	private static function _is_regexp_spam($comment)
	{
		// Fields
		$fields = array(
			'ip',
			'host',
			'body',
			'email',
			'author',
		);

		// Regexp
		$patterns = array(
			array(
				'host'	=> '^(www\.)?\d+\w+\.com$',
				'body'	=> '^\w+\s\d+$',
				'email'	=> '@gmail.com$',
			),
			array(
				'body'	=> '\<\!.+?mfunc.+?\>',
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
				'body'	=> 'dating|sex|lotto|pharmacy',
				'email'	=> '@mail\.ru|@yandex\.',
			),
		);

		// Spammy author
		if ( $quoted_author = preg_quote($comment['author'], '/') ) {
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
				'email'	 => '@gmail.com$',
				'author' => '^[a-z0-9-\.]+\.[a-z]{2,6}$',
				'host'	 => sprintf(
					'^%s$',
					$quoted_author
				),
			);
		}

		// Hook
		$patterns = apply_filters(
			'antispam_bee_patterns',
			$patterns
		);

		// Empty?
		if ( ! $patterns ) {
			return false;
		}

		// Loop expressions
		foreach ($patterns as $pattern) {
			$hits = array();

			// Loop fields
			foreach ($pattern as $field => $regexp) {
				// Empty value?
				if ( empty($field) OR !in_array($field, $fields) OR empty($regexp) ) {
					continue;
				}

				// Ignore non utf-8 chars
				$comment[$field] = ( function_exists('iconv') ? iconv('utf-8', 'utf-8//TRANSLIT', $comment[$field]) : $comment[$field] );

				// Empty value?
				if ( empty($comment[$field]) ) {
					continue;
				}

				// Perform regex
				if ( @preg_match('/' .$regexp. '/isu', $comment[$field]) ) {
					$hits[$field] = true;
				}
			}

			if ( count($hits) === count($pattern) ) {
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
	* @param   string	$ip     Comment IP
	* @param   string	$url    Comment URL [optional]
	* @param   string	$email  Comment Email [optional]
	* @return  boolean		TRUE for suspicious comment
	*/

	private static function _is_db_spam($ip, $url = '', $email = '')
	{
		// Global
		global $wpdb;

		// Default
		$filter = array('`comment_author_IP` = %s');
		$params = array( wp_unslash($ip) );

		// Match the URL
		if ( ! empty($url) ) {
			$filter[] = '`comment_author_url` = %s';
			$params[] = wp_unslash($url);
		}

		// Match the E-mail
		if ( ! empty($email) ) {
			$filter[] = '`comment_author_email` = %s';
			$params[] = wp_unslash($email);
		}

		// Perform query
		$result = $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = 'spam' AND (%s) LIMIT 1",
					implode(' OR ', $filter)
				),
				$params
			)
		);

		return !empty($result);
	}


	/**
	* Check for country spam by (anonymized) IP
	*
	* @since   2.6.9
	* @change  2.6.9
	*
	* @param   string	$ip	IP address
	* @return  boolean       	TRUE if the comment is spam based on country filter
	*/

	private static function _is_country_spam($ip)
	{
		// Get options
		$options = self::get_options();

		// White & Black
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

		// Empty lists?
		if ( empty($white) && empty($black) ) {
			return false;
		}

		// IP 2 Country API
		$response = wp_safe_remote_head(
			esc_url_raw(
				sprintf(
					'https://api.ip2country.info/ip?%s',
					self::_anonymize_ip($ip)
				),
				'https'
			)
		);

		// Error by WP
		if ( is_wp_error($response) ) {
			return false;
		}

		// Response code check
		if ( wp_remote_retrieve_response_code($response) !== 200 ) {
			return false;
		}

		// Get country code
		$country = (string)wp_remote_retrieve_header($response, 'x-country-code');

		// Country code check
		if ( empty($country) OR strlen($country) !== 2 ) {
			return false;
		}

		// Dive into blacklist
		if ( ! empty($black) ) {
			return ( in_array($country, $black) );
		}

		// Dive into whitelist
		return ( ! in_array($country, $white) );
	}

	/**
	* Check for DNSBL spam
	*
	* @since   2.4.5
	* @change  2.4.5
	*
	* @param   string   $ip  IP address
	* @return  boolean       TRUE for reported IP
	*/

	private static function _is_dnsbl_spam($ip)
	{
		// Start request
		$response = wp_safe_remote_request(
			esc_url_raw(
				sprintf(
					'http://www.stopforumspam.com/api?ip=%s&f=json',
					$ip
				),
				'http'
			)
		);

		// Response error?
		if ( is_wp_error($response) ) {
			return false;
		}

		// Get JSON
		$json = wp_remote_retrieve_body($response);

		// Decode JSON
		$result = json_decode($json);

		// Empty data
		if ( empty($result->success) ) {
			return false;
		}

		// Return status
		return (bool) $result->ip->appears;
	}


	/**
	* Check for BBCode spam
	*
	* @since   2.5.1
	* @change  2.5.1
	*
	* @param   string   $body  Content of a comment
	* @return  boolean         TRUE for BBCode in content
	*/

	private static function _is_bbcode_spam($body)
	{
		return (bool) preg_match('/\[url[=\]].*\[\/url\]/is', $body);
	}


	/**
	* Check for an already approved e-mail address
	*
	* @since   2.0
	* @change  2.5.1
	*
	* @param   string   $email  E-mail address
	* @return  boolean          TRUE for a found entry
	*/

	private static function _is_approved_email($email)
	{
		// Global
		global $wpdb;

		// Search
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `comment_ID` FROM `$wpdb->comments` WHERE `comment_approved` = '1' AND `comment_author_email` = %s LIMIT 1",
				wp_unslash($email)
			)
		);

		// Found?
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
	* @param   string   $ip    Client IP
	* @param   string   $host  Client Host [optional]
	* @return  boolean         TRUE if fake IP
	*/

	private static function _is_fake_ip($client_ip, $client_host = false)
	{
		// Remote Host
		$host_by_ip = gethostbyaddr($client_ip);

		// IPv6
		if ( self::_is_ipv6($client_ip) ) {
			return $client_ip != $host_by_ip;
		}

		// IPv4 and Comment
		if ( empty($client_host) ) {
			$ip_by_host = gethostbyname($host_by_ip);

			if ( $ip_by_host === $host_by_ip ) {
				return false;
			}

		// IPv4 and Trackback
		} else {
			if ( $host_by_ip === $client_ip ) {
				return true;
			}

			$ip_by_host = gethostbyname($client_host);
		}

		if ( strpos( $client_ip, self::_cut_ip($ip_by_host) ) === false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check for unwanted languages
	 *
	 * @since   2.0
	 * @change  2.6.6
	 * @change  2.7.0
	 *
	 * @param  string $comment_content Content of the comment.
	 *
	 * @return boolean TRUE if it is spam
	 */

	private static function _is_lang_spam( $comment_content ) {
		// User defined language
		$allowed_lang = self::get_option( 'translate_lang' );

		// Make comment text plain
		$comment_text = wp_strip_all_tags( $comment_content );

		// Skip if empty values
		if ( empty( $allowed_lang )
		     || empty( $comment_text )
		) {
			return false;
		}

		// Trim comment text
		if ( ! $query_text = wp_trim_words( $comment_text, 10, '' ) ) {
			return false;
		}

		/**
		 * Filter the Google Translate API key to be used.
		 *
		 * @since 2.7.0
		 *
		 * @param string $key API key to use.
		 *
		 * @return string Modified API key.
		 */
		$key = apply_filters(
			'ab_google_translate_api_key',
			base64_decode(
				strrev( 'B9GcXFjbjdULkdDUfh1SOlzZ2FzMhF1Mt1kRWVTWoVHR5NVY6lUQ' )
			)
		);

		// Start request
		$response = wp_safe_remote_request(
			add_query_arg(
				array(
					'q'   => rawurlencode( $query_text ),
					'key' => $key,
				),
				'https://www.googleapis.com/language/translate/v2/detect'
			)
		);

		// Skip on error
		if ( is_wp_error( $response )
		     || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		// Get JSON from content
		if ( ! $json = wp_remote_retrieve_body( $response ) ) {
			return false;
		}

		// Decode JSON
		if ( ! $data_array = json_decode( $json, true ) ) {
			return false;
		}

		// Get detected language
		if ( ! $detected_lang = @$data_array['data']['detections'][0][0]['language'] ) {
			return false;
		}

		return ( $detected_lang != $allowed_lang );
	}

	/**
	* Trim IP addresses
	*
	* @since   0.1
	* @change  2.5.1
	*
	* @param   string   $ip       Original IP
	* @param   boolean  $cut_end  Shortening the end?
	* @return  string             Shortened IP
	*/

	private static function _cut_ip($ip, $cut_end = true)
	{
		$separator = ( self::_is_ipv4($ip) ? '.' : ':' );

		return str_replace(
			( $cut_end ? strrchr( $ip, $separator) : strstr( $ip, $separator) ),
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
	* @param   string  $ip  Original IP
	* @return  string       Anonymous IP
	*/

	private static function _anonymize_ip($ip)
	{
		if ( self::_is_ipv4($ip) ) {
			return self::_cut_ip($ip). '.0';
		}

		return self::_cut_ip($ip, false). ':0:0:0:0:0:0:0';
	}


	/**
	* Rotates the IP address
	*
	* @since   2.4.5
	* @change  2.4.5
	*
	* @param   string   $ip  IP address
	* @return  string        Turned IP address
	*/

	private static function _reverse_ip($ip)
	{
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
	* @param   string   $ip  IP to validate
	* @return  integer       TRUE if IPv4
	*/

	private static function _is_ipv4($ip)
	{
		if ( function_exists('filter_var') ) {
			return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
		} else {
			return preg_match('/^\d{1,3}(\.\d{1,3}){3,3}$/', $ip);
		}
	}


	/**
	* Check for an IPv6 address
	*
	* @since   2.6.2
	* @change  2.6.4
	*
	* @param   string   $ip  IP to validate
	* @return  boolean       TRUE if IPv6
	*/

	private static function _is_ipv6($ip)
	{
		if ( function_exists('filter_var') ) {
			return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
		} else {
			return ! self::_is_ipv4($ip);
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

	private static function _is_mobile()
	{
		return strpos(TEMPLATEPATH, 'wptouch');
	}



/*
*	############################
*	#####  SPAM-TREATMENT  #####
*	############################
*/

	/**
	* Execution of the delete/marking process
	*
	* @since   0.1
	* @change  2.6.0
	*
	* @param   array    $comment  Untreated commentary data
	* @param   string   $reason   Reason for suspicion
	* @param   boolean  $is_ping  Ping (yes or no) [optional]
	* @return  array    $comment  Treated commentary data
	*/

	private static function _handle_spam_request($comment, $reason, $is_ping = false)
	{
		// Options
		$options = self::get_options();

		// Settings
		$spam_remove = !$options['flag_spam'];
		$spam_notice = !$options['no_notice'];

		// Filter settings
		$ignore_filter = $options['ignore_filter'];
		$ignore_type = $options['ignore_type'];
		$ignore_reason = in_array($reason, (array)$options['ignore_reasons']);

		// Remember spam
		self::_update_spam_log($comment);
		self::_update_spam_count();
		self::_update_daily_stats();

		// Delete spam
		if ( $spam_remove ) {
			self::_go_in_peace();
		}

		// Handle types
		if ( $ignore_filter && (( $ignore_type == 1 && $is_ping ) or ( $ignore_type == 2 && !$is_ping )) ) {
			self::_go_in_peace();
		}

		// Spam reason
		if ( $ignore_reason ) {
			self::_go_in_peace();
		}
		self::$_reason = $reason;

		// Mark spam
		add_filter(
			'pre_comment_approved',
			create_function(
				'',
				'return "spam";'
			)
		);

		// Send e-mail
		add_filter(
			'trackback_post',
			array(
				__CLASS__,
				'send_mail_notification'
			)
		);
		add_filter(
			'comment_post',
			array(
				__CLASS__,
				'send_mail_notification'
			)
		);

		// Spam reason as comment meta
		if ( $spam_notice ) {
			add_filter(
				'comment_post',
				array(
					__CLASS__,
					'add_spam_reason_to_comment'
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
	* @param   array   $comment	Array with commentary data
	* @return  mixed   		FALSE in case of error
	*/

	private static function _update_spam_log($comment)
	{
		// Skip logfile?
		if ( ! defined('ANTISPAM_BEE_LOG_FILE') OR ! ANTISPAM_BEE_LOG_FILE OR ! is_writable(ANTISPAM_BEE_LOG_FILE) OR validate_file(ANTISPAM_BEE_LOG_FILE) === 1 ) {
			return false;
		}

		// Compose entry
		$entry = sprintf(
			'%s comment for post=%d from host=%s marked as spam%s',
			current_time('mysql'),
			$comment['comment_post_ID'],
			$comment['comment_author_IP'],
			PHP_EOL
		);

		// Write
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

	private static function _go_in_peace()
	{
		status_header(403);
		die('Spam deleted.');
	}


	/**
	* Return real client IP
	*
	* @since   2.6.1
	* @change  2.6.1
	*
	* @return  mixed  $ip  Client IP
	*/

	public static function get_client_ip()
	{
		if ( isset($_SERVER['HTTP_CLIENT_IP']) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if ( isset($_SERVER['HTTP_X_FORWARDED']) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		} else if ( isset($_SERVER['HTTP_FORWARDED_FOR']) ) {
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if ( isset($_SERVER['HTTP_FORWARDED']) ) {
			$ip = $_SERVER['HTTP_FORWARDED'];
		} else if ( isset($_SERVER['REMOTE_ADDR']) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			return '';
		}

		if ( strpos($ip, ',') !== false ) {
			$ips = explode(',', $ip);
			$ip = trim(@$ips[0]);
		}

		if ( function_exists('filter_var') ) {
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
	* @param   integer  $comment_id  Comment ID
	*/

	public static function add_spam_reason_to_comment( $comment_id )
	{
		add_comment_meta(
			$comment_id,
			'antispam_bee_reason',
			self::$_reason
		);
	}


	/**
	* Delete spam reason as comment data
	*
	* @since   2.6.0
	* @change  2.6.0
	*
	* @param   integer  $comment_id  Comment ID
	*/

	public static function delete_spam_reason_by_comment( $comment_id )
	{
		delete_comment_meta(
			$comment_id,
			'antispam_bee_reason'
		);
	}

	/**
	 * Get the current post ID.
	 *
	 * @since   2.7.1
	 *
	 * @param   integer  $comment_id  Comment ID
	 */
	public static function populate_post_id() {

		if ( null === self::$_current_post_id ) {
			self::$_current_post_id = get_the_ID();
		}
	}


	/**
	*Send notification via e-mail
	*
	* @since   0.1
	* @change  2.5.7
	*
	* @hook    string  antispam_bee_notification_subject  Custom subject for notification mails
	*
	* @param   intval  $id  ID des Kommentars
	* @return  intval  $id  ID des Kommentars
	*/

	public static function send_mail_notification($id)
	{
		// Options
		$options = self::get_options();

		// No notification?
		if ( !$options['email_notify'] ) {
			return $id;
		}

		// Comment
		$comment = get_comment($id, ARRAY_A);

		// No values?
		if ( empty($comment) ) {
			return $id;
		}

		// Parent Post
		if ( ! $post = get_post($comment['comment_post_ID']) ) {
			return $id;
		}

		// Load the language
		self::load_plugin_lang();

		// Subject
		$subject = sprintf(
			'[%s] %s',
			stripslashes_deep(
				html_entity_decode(
					get_bloginfo('name'),
					ENT_QUOTES
				)
			),
			esc_html__('Comment marked as spam', 'antispam-bee')
		);

		// Content
		if ( !$content = strip_tags(stripslashes($comment['comment_content'])) ) {
			$content = sprintf(
				'-- %s --',
				esc_html__('Content removed by Antispam Bee', 'antispam-bee')
			);
		}

		// Body
		$body = sprintf(
			"%s \"%s\"\r\n\r\n",
			esc_html__('New spam comment on your post', 'antispam-bee'),
			strip_tags($post->post_title)
		).sprintf(
			"%s: %s\r\n",
			esc_html__('Author', 'antispam-bee'),
			( empty($comment['comment_author']) ? '' : strip_tags($comment['comment_author']) )
		).sprintf(
			"URL: %s\r\n",
			// empty check exists
			esc_url($comment['comment_author_url'])
		).sprintf(
			"%s: %s\r\n",
			esc_html__('Type', 'antispam-bee'),
			esc_html__( ( empty($comment['comment_type']) ? 'Comment' : 'Trackback' ), 'antispam-bee' )
		).sprintf(
			"Whois: http://whois.arin.net/rest/ip/%s\r\n",
			$comment['comment_author_IP']
		).sprintf(
			"%s: %s\r\n\r\n",
			esc_html__('Spam Reason', 'antispam-bee'),
			esc_html__(self::$defaults['reasons'][self::$_reason], 'antispam-bee')
		).sprintf(
			"%s\r\n\r\n\r\n",
			$content
		).(
			EMPTY_TRASH_DAYS ? (
				sprintf(
					"%s: %s\r\n",
					esc_html__('Trash it', 'antispam-bee'),
					admin_url('comment.php?action=trash&c=' .$id)
				)
			) : (
				sprintf(
					"%s: %s\r\n",
					esc_html__('Delete it', 'antispam-bee'),
					admin_url('comment.php?action=delete&c=' .$id)
				)
			)
		).sprintf(
				"%s: %s\r\n",
			esc_html__('Approve it', 'antispam-bee'),
			admin_url('comment.php?action=approve&c=' .$id)
		).sprintf(
			"%s: %s\r\n\r\n",
			esc_html__('Spam list', 'antispam-bee'),
			admin_url('edit-comments.php?comment_status=spam')
		).sprintf(
			"%s\r\n%s\r\n",
			esc_html__('Notify message by Antispam Bee', 'antispam-bee'),
			esc_html__('http://antispambee.com', 'antispam-bee')
		);

		// Send
		wp_mail(
			get_bloginfo('admin_email'),
			apply_filters(
				'antispam_bee_notification_subject',
				$subject
			),
			$body
		);

		return $id;
	}



/*
*	############################
*	#######  STATISTICS  #######
*	############################
*/

	/**
	* Return the number of spam comments
	*
	* @since   0.1
	* @change  2.4
	*
	* @param   intval  $count  Number of spam comments
	*/

	private static function _get_spam_count()
	{
		// Init
		$count = self::get_option('spam_count');

		// Fire
		return ( get_locale() == 'de_DE' ? number_format($count, 0, '', '.') : number_format_i18n($count) );
	}


	/**
	* Output the number of spam comments
	*
	* @since   0.1
	* @change  2.4
	*/

	public static function the_spam_count()
	{
		echo esc_html( self::_get_spam_count() );
	}


	/**
	* Update the number of spam comments
	*
	* @since   0.1
	* @change  2.6.1
	*/

	private static function _update_spam_count()
	{
		// Skip if not enabled
		if ( ! self::get_option('dashboard_count') ) {
			return;
		}

		self::_update_option(
			'spam_count',
			intval( self::get_option('spam_count') + 1 )
		);
	}


	/**
	* Update statistics
	*
	* @since   1.9
	* @change  2.6.1
	*/

	private static function _update_daily_stats()
	{
		// Skip if not enabled
		if ( ! self::get_option('dashboard_chart') ) {
			return;
		}

		// Init
		$stats = (array)self::get_option('daily_stats');
		$today = (int)strtotime('today');

		// Count up
		if ( array_key_exists($today, $stats) ) {
			$stats[$today] ++;
		} else {
			$stats[$today] = 1;
		}

		// Sort
		krsort($stats, SORT_NUMERIC);

		// Save
		self::_update_option(
			'daily_stats',
			array_slice($stats, 0, 31, true)
		);
	}

	/**
	 * Returns the secret of a post used in the textarea name attribute.
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	public static function get_secret_name_for_post( $post_id ) {

		$secret = substr( sha1( md5( self::$_salt . (int) $post_id ) ), 0, 10 );

		/**
		 * Filters the secret for a post, which is used in the textarea name attribute.
		 *
		 * @param string $secret The secret.
		 * @param int    $post_id The post ID.
		 */
		return apply_filters(
			'ab_get_secret_name_for_post',
			$secret,
			(int) $post_id
		);

	}

	/**
	 * Returns the secret of a post used in the textarea id attribute.
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	public static function get_secret_id_for_post( $post_id ) {

		$secret = substr( sha1( md5( 'comment-id' . self::$_salt . (int) $post_id ) ), 0, 10 );

		/**
		 * Filters the secret for a post, which is used in the textarea id attribute.
		 *
		 * @param string $secret The secret.
		 * @param int    $post_id The post ID.
		 */
		return apply_filters(
			'ab_get_secret_id_for_post',
			$secret,
			(int) $post_id
		);
	}
}


// Fire
add_action(
	'plugins_loaded',
	array(
		'Antispam_Bee',
		'init'
	)
);

// Activation
register_activation_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'activate'
	)
);

// Deactivation
register_deactivation_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'deactivate'
	)
);

// Uninstall
register_uninstall_hook(
	__FILE__,
	array(
		'Antispam_Bee',
		'uninstall'
	)
);
