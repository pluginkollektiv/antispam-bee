<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\Components;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;

/**
 * Marks spam comments for deletion if they have a specific reason.
 */
class DeleteForReasons extends ControllableBase {
	protected static $slug = "asb-delete-for-reasons";
	protected static $marks_as_delete = true;

	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && $item['asb_marked_as_delete'] === true ) {
			return $item;
		}

		$reasons = (array) Settings::get_option( static::get_option_name( 'reasons' ), $item['asb_item_type'] );
		if ( ! $reasons ) {
			return $item;
		}

		if ( ! empty( array_intersect( $item['asb_reasons'], array_keys( $reasons ) ) ) ) {
			$item['asb_marked_as_delete'] = true;
		}

		return $item;
	}

	public static function get_name() {
		return __( 'Delete by reasons', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'Delete comments by spam reasons', 'antispam-bee' );
	}

	public static function get_description() {
		return null;
	}

	public static function get_options() {
		// Fetch all rules
		// All item types
		$rules = Rules::get();

		$options = [];
		foreach ( self::get_supported_types() as $type ) {
			$filtered_rules = Components::filter( $rules, [ 'type' => $type ] );
			$checkbox_options = [];

			// Todo: we need to filter out the positive rules here, like Approved Email and Gravatar.
			// Maybe we can add a flag to those rules, like `is_positive` or something like that.
			foreach ( $filtered_rules as $rule ) {
				$checkbox_options[ $rule::get_slug() ] = $rule::get_name();
			}

			$options[] = [
				'valid_for' => $type,
				'label' => __( 'Reasons', 'antispam-bee' ),
				'type' => 'checkbox-group',
				'options' => $checkbox_options,
				'option_name' => 'reasons',
				'sanitize' => function( $value ) use ( $checkbox_options ) {
					return Sanitize::checkbox_group( $value, $checkbox_options );
				}
			];
		}

		return $options;
	}
}
