<?php

namespace AntispamBee\GeneralOptions;

use AntispamBee\Admin\Fields\Text;
use AntispamBee\Helpers\Sanitize;

class DeleteOldSpam extends Base {

	protected static $slug = 'delete-spam-cronjob-enabled';

	public static function get_name() {
		return __( 'Delete old spam', 'antispam-bee' );
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
				'type'        => 'inline',
				'input'       => new Text(
					'general',
					[
						'input_type'  => 'number',
						'input_size'  => 'small',
						'option_name' => 'delete_spam_cronjob_days',
						'sanitize'    => function ( $value ) {
							return absint( $value );
						},
					],
					static::class
				),
				'option_name' => 'active',
				'label'       => esc_html__( 'Delete existing spam after %s days', 'antispam-bee' ),
				'description' => esc_html__( 'Cleaning up the database from old entries', 'antispam-bee' ),
				'sanitize'    => function ( $value ) {
					return Sanitize::checkbox( $value );
				},
			],
		];
	}
}
