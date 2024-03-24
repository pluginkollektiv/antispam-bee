<?php
/**
 * Delete For Reasons Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;

/**
 * Marks spam comments for deletion if they have a specific reason.
 */
class DeleteForReasons extends ControllableBase {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-delete-for-reasons';

	/**
	 * This post processor marks items for deletion.
	 *
	 * @var bool
	 */
	protected static $marks_as_delete = true;

	/**
	 * Process an item, i.e. mark it for deletion.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( array $item ): array {
		if ( isset( $item['asb_marked_as_delete'] ) && true === $item['asb_marked_as_delete'] ) {
			return $item;
		}

		$reasons = (array) Settings::get_option( static::get_option_name( 'reasons' ), $item['reaction_type'] );
		if ( ! $reasons ) {
			return $item;
		}

		if ( ! empty( array_intersect( $item['asb_reasons'], array_keys( $reasons ) ) ) ) {
			$item['asb_marked_as_delete'] = true;
		}

		return $item;
	}

	/**
	 * Get element name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Delete by reasons', 'antispam-bee' );
	}

	/**
	 * Get element label (optional).
	 *
	 * @return string|null
	 */
	public static function get_label(): ?string {
		return __( 'Delete comments by spam reasons', 'antispam-bee' );
	}

	/**
	 * Get element description (optional).
	 *
	 * @return string|null
	 */
	public static function get_description(): ?string {
		return null;
	}


	/**
	 * Get post processor options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public static function get_options(): array {
		$options = [];
		foreach ( self::get_supported_types() as $type ) {
			// @todo: disable the reasons checkboxes if the rule is not active.
			$filtered_rules   = Rules::get_spam_rules( $type );
			$checkbox_options = [];

			foreach ( $filtered_rules as $rule ) {
				if ( $rule::is_invisible() ) {
					continue;
				}
				$checkbox_options[ $rule::get_slug() ] = $rule::get_name();
			}

			$options[] = [
				'valid_for'   => $type,
				'label'       => __( 'Reasons', 'antispam-bee' ),
				'type'        => 'checkbox-group',
				'options'     => $checkbox_options,
				'option_name' => 'reasons',
				'sanitize'    => function ( $value ) use ( $checkbox_options ) {
					return Sanitize::checkbox_group( $value, $checkbox_options );
				},
			];
		}

		return $options;
	}
}
