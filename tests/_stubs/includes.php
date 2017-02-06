<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) );
}

function register_activation_hook() { }

function register_deactivation_hook() { }

function register_uninstall_hook() { }

function wp_cache_get() { return false; }

function wp_cache_set() { return true; }

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

function plugin_basename() { return dirname( dirname( __DIR__ ) ); }

function __( $text, $domain ) { return $text; }