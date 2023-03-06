<?php

namespace AntispamBee\GeneralOptions;

class IgnorePings extends Base {
	protected static $slug = 'ignore-pings';

	public static function get_name() {
		return __( 'Pings', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Do not check linkbacks (pingbacks, trackbacks)', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'No spam check for link notifications', 'antispam-bee' );
	}
}
