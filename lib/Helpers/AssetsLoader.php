<?php
/**
 * Class to register client-side assets (scripts and stylesheets).
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

use const AntispamBee\ANTISPAM_BEE_FILE;
use const AntispamBee\ANTISPAM_BEE_PATH;
use const AntispamBee\ANTISPAM_BEE_URL;
use const AntispamBee\ANTISPAM_BEE_VERSION;

/**
 * AssetsLoader helper.
 */
class AssetsLoader {
	/**
	 * Registers all assets used in frontend and backend.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ], 11 );
		add_action( 'admin_footer', [ __CLASS__, 'include_svg_icons' ], 9999 );
	}

	/**
	 * Register the assets for the backend.
	 */
	public static function register_assets() {
		$backend_assets_path  = 'build/backend.asset.php';
		$backend_scripts_path = 'build/backend.js';
		$backend_style_path   = 'build/backend.css';

		if ( file_exists( ANTISPAM_BEE_PATH . $backend_assets_path ) ) {
			$backend_asset = require ANTISPAM_BEE_PATH . $backend_assets_path;
		} else {
			$backend_asset = [
				'dependencies' => [
					'wp-i18n',
				],
				'version'      => ANTISPAM_BEE_VERSION,
			];
		}

		// Register the bundled block JS file.
		if ( file_exists( ANTISPAM_BEE_PATH . $backend_scripts_path ) ) {
			wp_register_script(
				'antispam-bee-backend',
				ANTISPAM_BEE_URL . $backend_scripts_path,
				$backend_asset['dependencies'],
				$backend_asset['version'],
				true
			);
		}

		// Register optional editor only styles.
		if ( file_exists( ANTISPAM_BEE_PATH . $backend_style_path ) ) {
			wp_register_style(
				'antispam-bee-backend',
				ANTISPAM_BEE_URL . $backend_style_path,
				[],
				$backend_asset['version']
			);
		}

		if ( in_array( 'wp-i18n', $backend_asset['dependencies'], true ) ) {
			wp_set_script_translations( 'antispam-bee-backend', 'antispam-bee' );
		}
	}

	/**
	 * Enqueue the backend assets.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_script( 'antispam-bee-backend' );
		wp_enqueue_style( 'antispam-bee-backend' );

		// Adding legacy scripts.
		self::add_dashboard_script();
	}

	/**
	 * Print dashboard scripts
	 *
	 * @since  1.9.0
	 * @since  2.5.8
	 */
	private static function add_dashboard_script() {
		if ( ! Settings::get_option( 'daily_stats' ) ) {
			return;
		}

		$plugin = get_plugin_data( ANTISPAM_BEE_FILE );

		// Todo: check if statistics dashboard widget is working.
		wp_enqueue_script(
			'raphael',
			plugins_url( 'src/legacy/raphael.min.js', ANTISPAM_BEE_FILE ),
			[],
			'2.1.0',
			true
		);

		wp_enqueue_script(
			'ab-raphael',
			plugins_url( 'src/legacy/raphael.helper.js', ANTISPAM_BEE_FILE ),
			[ 'raphael' ],
			$plugin['Version'],
			true
		);

		wp_enqueue_script(
			'ab_chart_js',
			plugins_url( 'src/legacy/dashboard.js', ANTISPAM_BEE_FILE ),
			[ 'jquery', 'ab-raphael' ],
			$plugin['Version'],
			true
		);
	}

	/**
	 * Add SVG definitions to footer.
	 */
	public static function include_svg_icons() {
		$svg_icons = ANTISPAM_BEE_PATH . 'build/images/icons/sprite.svg';

		if ( file_exists( $svg_icons ) ) {
			echo '<div style="position: absolute; width: 0; height: 0; overflow: hidden;">';
			require_once $svg_icons;
			echo '</div>';
		}
	}
}
