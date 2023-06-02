<?php

namespace AntispamBee\GeneralOptions;

class Statistics extends Base {
	protected static $slug = 'statistics-on-dashboard';

	public static function get_name() {
		return __( 'Statistics', 'antispam-bee' );
	}

	public static function get_label() {
		return esc_html__( 'Spam counter on the dashboard', 'antispam-bee' );
	}

	public static function get_description() {
		return esc_html__( 'Amount of identified spam comments', 'antispam-bee' );
	}
}
