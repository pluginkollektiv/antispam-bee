<?php
/**
 * Helper to get and update statistics.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Class StatsHelpers
 */
class StatsHelpers {

	public function init() {
		add_action( 'antispam_bee_update_daily_stats', [ $this, 'update_daily_stats' ] );
	}

	/**
	 * Return the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public function get_spam_count() {
		$count = OptionsHelper::get_option( 'spam_count' );

		return ( get_locale() === 'de_DE' ? number_format( $count, 0, '', '.' ) : number_format_i18n( $count ) );
	}

	/**
	 * Output the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.4
	 */
	public function the_spam_count() {
		echo esc_html( $this->get_spam_count() );
	}

	/**
	 * Update the number of spam comments
	 *
	 * @since  0.1
	 * @since  2.6.1
	 */
	public function update_spam_count() {
		if ( ! OptionsHelper::get_option( 'dashboard_count' ) ) {
			return;
		}

		OptionsHelper::update_option(
			'spam_count',
			intval( OptionsHelper::get_option( 'spam_count' ) + 1 )
		);
	}

	/**
	 * Update statistics
	 *
	 * @since  1.9
	 * @since  2.6.1
	 */
	public function update_daily_stats() {
		if ( ! OptionsHelper::get_option( 'dashboard_chart' ) ) {
			return;
		}

		$stats = (array) OptionsHelper::get_option( 'daily_stats' );
		$today = (int) strtotime( 'today' );

		if ( array_key_exists( $today, $stats ) ) {
			$stats[ $today ] ++;
		} else {
			$stats[ $today ] = 1;
		}

		krsort( $stats, SORT_NUMERIC );

		OptionsHelper::update_option(
			'daily_stats',
			array_slice( $stats, 0, 31, true )
		);
	}
}
