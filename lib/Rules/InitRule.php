<?php

namespace AntispamBee\Rules;

use AntispamBee\Helpers\InterfaceHelper;
use AntispamBee\Interfaces\Controllable;

trait InitRule {
	public static function init() {
		add_filter(
			'asb_rules',
			function ( $rules ) {
				$rule = [];
				$rule['verifiable'] = self::class;
				if ( InterfaceHelper::class_implements_interface( self::class, Controllable::class ) ) {
					$rule['controllable'] = self::class;
				}

				$rules[] = $rule;
				return $rules;
			}
		);
	}
}
