<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\Components;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Helpers\Settings;

class DeleteForReasons implements PostProcessor, Controllable {

	use IsActive;
	use InitPostProcessor;

	// Todo: Test and maybe complete
	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && $item['asb_marked_as_delete'] === true ) {
			return $item;
		}

		$reasons = (array) Settings::get_option( 'asb_delete_reasons', $item['asb_item_type'] );
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

	public static function get_slug() {
		return 'asb-delete-for-reasons';
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
			foreach ( $filtered_rules as $rule ) {
				$checkbox_options[ $rule::get_slug() ] = $rule::get_name();
			}

			$options[] = [
				'valid_for' => $type,
				'label' => __( 'Reasons', 'antispam-bee' ),
				'type' => 'checkbox-group',
				'options' => $checkbox_options,
				'option_name' => 'asb_delete_reasons',
				'sanitize' => function( $value ) {
					return self::sanitize_input( $value );
				}
			];
		}

		return $options;
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function marks_as_delete() {
		return true;
	}

	protected static function sanitize_input( $value ) {
		// Todo: Add sanitize functions!
		return $value;
	}
}
