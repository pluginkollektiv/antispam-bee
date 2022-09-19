<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Sanitize;

class Statistics extends Base {

	protected static $slug = 'statistics-on-dashboard';

	protected static $only_custom_options = true;

	// Todo: Check why two controllables with the same name are not possible.
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
				'type' => 'checkbox',
				'option_name' => 'ab_dashboard_chart',
				'label' => esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ),
				'description' => esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ),
				'sanitize' => function( $value ) {
					return Sanitize::checkbox( $value );
				}
			],
			[
				'type' => 'checkbox',
				'option_name' => 'ab_dashboard_count',
				'label' => esc_html( 'Spam counter on the dashboard', 'antispam-bee' ),
				'description' => esc_html( 'Amount of identified spam comments', 'antispam-bee' ),
				'sanitize' => function( $value ) {
					return Sanitize::checkbox( $value );
				}
			],
		];
	}
}
