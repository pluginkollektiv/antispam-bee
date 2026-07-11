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
	 * Get the option name.
	 *
	 * @return string The option name.
	 */
	public static function get_name(): string {
		return __( 'Uninstall', 'antispam-bee' );
	}

	/**
	 * Get the option label.
	 *
	 * @return string|null The option label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Delete Antispam Bee data when uninstalling', 'antispam-bee' );
	}

	/**
	 * Get the option description.
	 *
	 * @return string|null The option description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'If checked, you will delete all data Antispam Bee creates, when uninstalling the plugin.', 'antispam-bee' );
	}
}
