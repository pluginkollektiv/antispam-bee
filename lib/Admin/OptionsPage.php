<?php
/**
 * Register the options page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

/**
 * Class OptionsPage
 */
class OptionsPage {

	/**
	 * Initialize the options page.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_sidebar_menu' ] );
	}

	/**
	 * Initialization of the option page.
	 *
	 * @since  0.1
	 * @since  2.4.3
	 */
	public function add_sidebar_menu() {
		$page = add_options_page(
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam_bee',
			[ $this, 'options_page' ]
		);

		add_action( 'admin_print_scripts-' . $page, [ $this, 'add_options_script' ] );
		add_action( 'admin_print_styles-' . $page, [ $this, 'add_options_style' ] );
	}

	/**
	 * Initialization of JavaScript
	 *
	 * @since  1.6
	 * @since  2.4
	 */
	public static function add_options_script() {
		// @todo: maybe load some more scripts.
	}


	/**
	 * Initialization of Stylesheets
	 *
	 * @since  1.6
	 * @since  2.4
	 */
	public static function add_options_style() {
		// @todo: maybe load some more styles.
	}


	/**
	 * Display the GUI.
	 */
	public static function options_page() {
		// @todo: use new options UI.
	}
}
