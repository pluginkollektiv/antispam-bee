<?php
/**
 * Plugin Name: Change Antispam Bee Salt
 */

add_filter(
	'ab_get_secret_name_for_post',
	function() {
		return 'secret';
	}
);
add_action(
	'wp_dashboard_setup',
	function() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}
);
