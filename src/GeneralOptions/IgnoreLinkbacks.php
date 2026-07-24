<?php
/**
 * Ignore linkbacks option.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

/**
 * Option to ignore linkbacks.
 */
class IgnoreLinkbacks extends Base {

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug = 'ignore-linkbacks';

	/**
	 * Get the option name.
	 *
	 * @return string The option name.
	 */
	public static function get_name(): string {
		return __( 'Linkbacks', 'antispam-bee' );
	}

	/**
	 * Get the option label.
	 *
	 * @return string|null The option label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Do not check linkbacks (pingbacks, trackbacks)', 'antispam-bee' );
	}

	/**
	 * Get the option description.
	 *
	 * @return string|null The option description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'No spam check for link notifications', 'antispam-bee' );
	}
}
