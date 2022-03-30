<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

class BBCode implements Verifiable, Controllable {

	use InitRule;
	use IsActive;

	public static function verify( $data ) {
		foreach ( $data as $value ) {
			if ( true === (bool) preg_match( '/\[url[=\]].*\[\/url\]/is', $value ) ) {
				return 1;
			}
		}

		return 0;
	}

	public static function get_name() {
		return __( 'BBCode', 'antispam-bee' );
	}

	public static function get_label() {
		return __( 'BBCode links are spam', 'antispam-bee' );
	}

	public static function get_description() {
		__( 'Review the comment contents for BBCode links', 'antispam-bee' );
	}

	public static function get_weight() {
		return 1.0;
	}

	public static function get_slug() {
		return 'asb-bbcode';
	}

	public static function is_final() {
		return false;
	}

	public static function get_options() {
		return [
			[
				'type' => 'input',
	            'input_type' => 'email|password|number...',
				'label' => __( 'BBCodes to allow', 'antispam-bee' ),
	            'option_name' => 'asb_deny_langcodes',
	            'options' => [ 'Option A', 'Option B' ],
	            'multiple' => true,
	            'placeholder' => 'My placeholder text',
	            'default' => 'Default value'
			],
			[
				'type' => 'select',
				'label' => __( 'BBCodes to allow', 'antispam-bee' ),
				'option_name' => 'asb_deny_langcodes',
				'options' => [
					[
						'value' => 1,
						'label' => 'Option A'
					],
					[
						'value' => 2,
						'label' => 'Option B'
					],
				],
				'multiple' => false,
				'placeholder' => 'My placeholder text',
				'default' => 'Default value'
			]
		];
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

}
