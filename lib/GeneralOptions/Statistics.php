<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Sanitize;

class Statistics extends Base {

	const DASHBOARD_CHART_OPTION = 'dashboard_chart';

	const DASHBOARD_COUNT_OPTION = 'dashboard_count';

	protected static $slug = 'statistics-on-dashboard';

	protected static $only_custom_options = true;

	public static function get_name() {
		return __( 'Statistics', 'antispam-bee' );
	}

	public static function get_label() {
		return null;
	}

	public static function get_description() {
		return null;
	}

	public static function get_options() {
		return [
			[
				'type'        => 'checkbox',
				'option_name' => static::DASHBOARD_CHART_OPTION,
				'label'       => esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ),
				'description' => esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ),
				'sanitize'    => function( $value ) {
					return Sanitize::checkbox( $value );
				},
			],
			[
				'type'        => 'checkbox',
				'option_name' => static::DASHBOARD_COUNT_OPTION,
				'label'       => esc_html( 'Spam counter on the dashboard', 'antispam-bee' ),
				'description' => esc_html( 'Amount of identified spam comments', 'antispam-bee' ),
				'sanitize'    => function( $value ) {
					return Sanitize::checkbox( $value );
				},
			],
		];
	}
}
