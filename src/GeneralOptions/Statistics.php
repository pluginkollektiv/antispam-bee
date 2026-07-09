<?php
/**
 * Statistics option.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

/**
 * Option to control spam statistics on dashboard.
 */
class Statistics extends Base {

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug = 'statistics-on-dashboard';

	/**
	 * Get the option name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Statistics', 'antispam-bee' );
	}

	/**
	 * Get the option label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return esc_html__( 'Spam counter on the dashboard', 'antispam-bee' );
	}

	/**
	 * Get the option description.
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return esc_html__( 'Amount of identified spam comments', 'antispam-bee' );
	}
}
