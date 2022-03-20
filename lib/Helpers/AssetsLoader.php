<?php
/**
 * Class to register client-side assets (scripts and stylesheets).
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Class AssetsLoader
 */
class AssetsLoader {
	/**
	 * Registers all assets used in frontend and backend.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 11 );
		add_action( 'admin_footer', [ $this, 'include_svg_icons' ], 9999 );
	}

	/**
	 * Register the assets for the backend.
	 */
	public function register_assets() {
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

		if ( in_array( 'wp-i18n',$backend_asset['dependencies'], true ) ) {
			wp_set_script_translations( 'antispam-bee-backend', 'antispam-bee' );
		}
	}

	/**
	 * Enqueue the backend assets.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'antispam-bee-backend' );
		wp_enqueue_style( 'antispam-bee-backend' );
	}

	/**
	 * Add SVG definitions to footer.
	 */
	public function include_svg_icons() {
		$svg_icons = ANTISPAM_BEE_PATH . 'build/images/icons/sprite.svg';

		if ( file_exists( $svg_icons ) ) {
			echo '<div style="position: absolute; width: 0; height: 0; overflow: hidden;">';
			require_once $svg_icons;
			echo '</div>';
		}
	}
}
