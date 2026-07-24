<?php
/**
 * DeleteForReasons Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use ReflectionException;

/**
 * Marks spam comments for deletion if they have a specific reason.
 */
class DeleteForReasons extends ControllableBase {

	/**
	 * Post-processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-delete-for-reasons';

	/**
	 * This post-processor marks items for deletion.
	 *
	 * @var bool
	 */
	protected static $marks_as_delete = true;

	/**
	 * Process an item, i.e. mark it for deletion.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
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
	 * Get the element label (optional).
	 *
	 * @return string|null The label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Delete comments by spam reasons', 'antispam-bee' );
	}

	/**
	 * Get the element description (optional).
	 *
	 * @return string|null The description, or null.
	 */
	public static function get_description(): ?string {
		return null;
	}

	/**
	 * Get the post-processor options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array<int, array<string, mixed>> The post-processor options.
	 * @throws ReflectionException
	 */
	public static function get_options(): array {
		$options = [];
		foreach ( self::get_supported_types() as $reaction_type ) {
			$filtered_rules   = Rules::get_spam_reason_rules( $reaction_type );
			$checkbox_options = [];

			foreach ( $filtered_rules as $rule ) {
				if ( $rule::is_invisible() ) {
					continue;
				}
				$checkbox_options[ $rule::get_slug() ] = $rule::get_name();
			}

			$options[] = [
				'valid_for'   => $reaction_type,
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

	/**
	 * Get the element name.
	 *
	 * @return string The name.
	 */
	public static function get_name(): string {
		return __( 'Delete by reasons', 'antispam-bee' );
	}
}
