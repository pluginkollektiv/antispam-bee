<?php

namespace AntispamBee\GeneralOptions;

class Uninstall extends Base {
	protected static $slug = 'delete-data-on-uninstall';

	public static function get_name() {
		return __( 'Uninstall', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Delete Antispam Bee data when uninstalling', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'If checked, you will delete all data Antispam Bee creates, when uninstalling the plugin.', 'antispam-bee' );
	}
}
