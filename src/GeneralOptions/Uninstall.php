<?php
/**
 * Uninstall option.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

/**
 * Option for uninstallation.
 */
class Uninstall extends Base {

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug = 'delete-data-on-uninstall';

	/**
	 * Get option name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Uninstall', 'antispam-bee' );
	}

	/**
	 * Get option label.
	 *
	 * @return string|null
	 */
	public static function get_label() {
		return __( 'Delete Antispam Bee data when uninstalling', 'antispam-bee' );
	}

	/**
	 * Get option description.
	 *
	 * @return string|null
	 */
	public static function get_description() {
		return __( 'If checked, you will delete all data Antispam Bee creates, when uninstalling the plugin.', 'antispam-bee' );
	}
}
