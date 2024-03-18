<?php
/**
 * Option whether to delete old spam.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

use AntispamBee\Admin\Fields\Text;
use AntispamBee\Helpers\Sanitize;

/**
 * Option whether to delete old spam.
 */
class DeleteOldSpam extends Base {

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug = 'delete-spam-cronjob-enabled';

	/**
	 * Get option name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Delete old spam', 'antispam-bee' );
	}

	/**
	 * Get option label.
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return null;
	}

	/**
	 * Get option description.
	 *
	 * @return null
	 */
	public static function get_description(): ?string {
		return null;
	}

	/**
	 * Get options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array|null
	 */
	public static function get_options(): array {
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
				// translators: Number of days inserted at placeholder.
				'label'       => esc_html__( 'Delete existing spam after %s days', 'antispam-bee' ),
				'description' => esc_html__( 'Cleaning up the database from old entries', 'antispam-bee' ),
				'sanitize'    => function ( $value ) {
					return Sanitize::checkbox( $value );
				},
			],
		];
	}
}
