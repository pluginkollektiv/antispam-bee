<?php

namespace AntispamBee\Rules;

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
		$rules[] = [
			'verifiable' => self::class,
		];

		return $rules;
	}
}