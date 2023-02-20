<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\GeneralOptions\Statistics;
use AntispamBee\Helpers\Settings;

/**
 * Post processor that is responsible for updating the daily stats which are e.g. used from the Dashboard widget.
 */
class UpdateDailyStats extends Base {

	protected static $slug = 'asb-update-daily-stats';

	public static function process( $item ) {
		if ( ! Settings::get_option( Statistics::get_option_name( Statistics::DASHBOARD_CHART_OPTION ) ) ) {
			return $item;
		}

		$stats = (array) Settings::get_option( 'daily_stats', '' );
		$today = (int) strtotime( 'today' );

		if ( array_key_exists( $today, $stats ) ) {
			$stats[ $today ] ++;
		} else {
			$stats[ $today ] = 1;
		}

		krsort( $stats, SORT_NUMERIC );

		Settings::update_option(
			'daily_stats',
			array_slice( $stats, 0, 31, true )
		);

		return $item;
	}
}
