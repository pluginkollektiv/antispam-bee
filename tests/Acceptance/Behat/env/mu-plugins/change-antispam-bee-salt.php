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