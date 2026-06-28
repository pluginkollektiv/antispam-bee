<?php
/**
 * Disable WordPress automatic updates and cron-based update checks during E2E tests.
 *
 * Without this, WordPress spawns a background cron request on each page load,
 * which can trigger an automatic update and put the site into maintenance mode
 * mid-test.
 *
 * @package AntispamBee
 */

if ( ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
	define( 'AUTOMATIC_UPDATER_DISABLED', true );
}

if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {
	define( 'WP_AUTO_UPDATE_CORE', false );
}

if ( ! defined( 'DISABLE_WP_CRON' ) ) {
	define( 'DISABLE_WP_CRON', true );
}

add_filter( 'auto_update_plugin', '__return_false' );
add_filter( 'auto_update_theme', '__return_false' );
add_filter( 'auto_update_translation', '__return_false' );
