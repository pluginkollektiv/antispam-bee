<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Helpers\Settings;

/**
 * Post processor that is responsible for updating the daily stats which are e.g. used from the Dashboard widget.
 */
class UpdateDailyStats implements PostProcessor {

	use IsActive;
	use InitPostProcessor;

	public static function process( $item ) {
		if ( ! Settings::get_option( 'dashboard_chart' ) ) {
			return $item;
		}

		$stats = (array) Settings::get_option( 'daily_stats' );
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

	public static function get_slug() {
		return 'asb-update-daily-stats';
	}

	// Todo: add a filter to make the supported types… filterable
	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function marks_as_delete() {
		return false;
	}
}

