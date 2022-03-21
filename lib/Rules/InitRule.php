<?php

namespace AntispamBee\Rules;

trait InitRule {
	public static function init() {
		add_filter(
			'asb_rules',
			function ( $rules ) {
				$rules[] = [
					'verifiable' => self::class,
				];

				return $rules;
			}
		);
	}
}
