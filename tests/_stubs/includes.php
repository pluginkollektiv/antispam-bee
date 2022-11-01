<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) );
}

function register_activation_hook() { }

function register_deactivation_hook() { }

function register_uninstall_hook() { }

function wp_cache_get() { return false; }

function wp_cache_set( $key, $data ) { return true; }

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
function esc_attr__( $text, $domain ) { return $text; }
function esc_html__( $text, $domain ) { return $text; }
function _e( $text, $domain ) { echo $text; }
function esc_attr_e( $text, $domain ) { echo $text; }
function esc_html_e( $text, $domain ) { echo $text; }
function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) { return true; }