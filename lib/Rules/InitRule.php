<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\InterfaceHelper;
use AntispamBee\Interfaces\Controllable;

trait InitRule {

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'asb_rules', [ __CLASS__, 'add_rule' ] );
	}

	/**
	 * Add the current rule class to the 'asb_rules' filter.
	 *
	 * @param array $rules The currently registered rules.
	 *
	 * @return array
	 */
	public static function add_rule( $rules ) {
		$rule = [];
		$rule['verifiable'] = self::class;
		if ( InterfaceHelper::class_implements_interface( self::class, Controllable::class ) ) {
			$rule['controllable'] = self::class;
		}

		$rules[] = $rule;
		return $rules;
	}
}
