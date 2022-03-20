<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Settings;

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

	public static function is_active( $type ) {
	}

	public static function get_slug() {
		return 'asb-send-email';
	}

	public static function get_supported_types() {
		return [ 'comment', 'trackback' ];
	}

	public static function marks_as_delete() {
		return false;
	}
}

