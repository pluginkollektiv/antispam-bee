<?php

namespace AntispamBee\Rules;

trait InitRule {
	public static function init() {
		add_filter( 'asb_rules', function( $rules ) {
			$rules[] = [
				'weight' => self::get_weight(),
				'name' => self::get_name(),
				'callable' => array( self::class, 'verify' ),
			];
		} );
	}
}