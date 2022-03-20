<?php
/**
 * Main plugin file to load other classes
 *
 * @package AntispamBee
 */

namespace AntispamBee;

use AntispamBee\Helpers\AssetsLoader;
use AntispamBee\Helpers\CommentsColumns;

/**
 * Init function of the plugin
 */
function init() {
	// Construct all modules to initialize.
	$modules = [
		'helpers_assets_loader'    => new AssetsLoader(),
		'helpers_comments_columns' => new CommentsColumns(),
	];

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', 'AntispamBee\init' );
