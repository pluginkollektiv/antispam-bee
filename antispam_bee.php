<?php
/**
 * Antispam Bee
 *
 * @package AntispamBee
 * @author  pluginkollektiv
 * @license GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: Antispam Bee
 * Plugin URI: https://antispambee.pluginkollektiv.org/
 * Description: Antispam plugin with a sophisticated toolset for effective day-to-day comment and trackback spam-fighting. Built with data protection and privacy in mind.
 * Version: 3.0.0-alpha.4
 * Author: pluginkollektiv
 * Author URI: https://pluginkollektiv.org
 * Text Domain: antispam-bee
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace AntispamBee;

define( __NAMESPACE__ . '\MAIN_PLUGIN_FILE', __FILE__ );
define( __NAMESPACE__ . '\PLUGIN_PATH', plugin_dir_path( MAIN_PLUGIN_FILE ) );

// The pre_init functions check the compatibility of the plugin and calls the init function, if check were successful.
pre_init();

/**
 * Pre init function to check the plugins compatibility.
 */
function pre_init() {
	// Load the translation, as they might be needed in pre_init.
	add_action( 'plugins_loaded', __NAMESPACE__ . '\load_textdomain', 5 );

	// Check, if the min. required PHP version is available and if not, show an admin notice.
	if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\min_php_version_error' );

		// Stop the further processing of the plugin.
		return;
	}

	// Check, if the DOMDocument class exists.
	if ( ! class_exists( 'DOMDocument' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\domdocument_class_error' );

		// Stop the further processing of the plugin.
		return;
	}

	if ( file_exists( PLUGIN_PATH . 'composer.json' ) && ! file_exists( PLUGIN_PATH . 'vendor/autoload.php' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\autoloader_missing' );

		// Stop the further processing of the plugin.
		return;
	} else {
		$autoloader = PLUGIN_PATH . 'vendor/autoload.php';

		if ( is_readable( $autoloader ) ) {
			include $autoloader;
		}
	}

	// If all checks were successful, load the plugin.
	require_once PLUGIN_PATH . 'src/load.php';
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function load_textdomain() {
	load_plugin_textdomain( 'antispam-bee' );
}

/**
 * Show a admin notice error message, if the PHP version is too low
 */
function min_php_version_error() {
	echo '<div class="error"><p>';
	esc_html_e( 'Antispam Bee requires PHP version 7.2 or higher to function properly. Please upgrade PHP or deactivate Antispam Bee.', 'antispam-bee' );
	echo '</p></div>';
}

/**
 * Show a admin notice error message, if the PHP version is too low
 */
function domdocument_class_error() {
	echo '<div class="error"><p>';
	esc_html_e( 'Antispam Bee requires the DOMDocument PHP class. Please install the PHP DOM/XML extension.', 'antispam-bee' );
	echo '</p></div>';
}

/**
 * Show a admin notice error message, if the PHP version is too low
 */
function autoloader_missing() {
	echo '<div class="error"><p>';
	esc_html_e( 'Antispam Bee is missing the Composer autoloader file. Please run `composer install --no-dev -o` in the root folder of the plugin or use a release version including the `vendor` folder.', 'antispam-bee' );
	echo '</p></div>';
}
