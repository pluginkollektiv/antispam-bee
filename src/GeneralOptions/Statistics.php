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
	 * Get option name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Statistics', 'antispam-bee' );
	}

	/**
	 * Get option label.
	 *
	 * @return string|null
	 */
	public static function get_label() {
		return esc_html__( 'Spam counter on the dashboard', 'antispam-bee' );
	}

	/**
	 * Get option description.
	 *
	 * @return string|null
	 */
	public static function get_description() {
		return esc_html__( 'Amount of identified spam comments', 'antispam-bee' );
	}
}
