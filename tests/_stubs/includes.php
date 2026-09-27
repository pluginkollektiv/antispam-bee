<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) );
}

function register_activation_hook( $file, $callback ) { }

function register_deactivation_hook( $file, $callback ) { }

function register_uninstall_hook( $file, $callback ) { }

function wp_cache_get( $key ) { return false; }

function wp_cache_set( $key, $data ) { return true; }

function wp_cache_delete( $key ) { return true; }

function wp_parse_args( $args, $defaults = '' ) {
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		parse_str( $args, $r );
	}

	if ( is_array( $defaults ) ) {
		return array_merge( $defaults, $r );
	}

	return $r;
}

function plugin_basename( $file ) { return dirname( dirname( __DIR__ ) ); }

function __( $text, $domain ) { return $text; }
function esc_attr__( $text, $domain ) { return $text; }
function esc_html__( $text, $domain ) { return $text; }
function _e( $text, $domain ) { echo $text; }
function esc_attr_e( $text, $domain ) { echo $text; }
function esc_html_e( $text, $domain ) { echo $text; }
function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) { return true; }

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
}

/**
 * In-memory transients, so a cached lookup behaves the way it does in production.
 * Reset between tests with `$GLOBALS['asb_test_transients'] = [];`.
 */
$GLOBALS['asb_test_transients'] = [];

function get_transient( $key ) {
	return $GLOBALS['asb_test_transients'][ $key ] ?? false;
}

function set_transient( $key, $value, $expiration = 0 ) {
	$GLOBALS['asb_test_transients'][ $key ] = $value;

	return true;
}

function delete_transient( $key ) {
	unset( $GLOBALS['asb_test_transients'][ $key ] );

	return true;
}
