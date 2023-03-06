<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Sanitize;

class Statistics extends Base {
	protected static $slug = 'statistics-on-dashboard';

	public static function get_name() {
		return __( 'Statistics', 'antispam-bee' );
	}

	public static function get_label() {
		return esc_html( 'Spam counter on the dashboard', 'antispam-bee' );
	}

	public static function get_description() {
		return esc_html( 'Amount of identified spam comments', 'antispam-bee' );
	}
}
