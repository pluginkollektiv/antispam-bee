<?php

namespace AntispamBee\Handlers;

use AntispamBee\Interfaces\Verifiable;

class Rules {
	protected $type;
	protected $spam_reasons = [];
	protected $no_spam_reasons = [];

	public function __construct( $type ) {
		$this->type = $type;
	}

	public function apply( $item ) {
		$item['asb_item_type'] = $this->type;
		$rules = self::get( $this->type, true );

		$score = 0.0;
		foreach ( $rules as $rule ) {
			$verify_function = isset( $rule['verifiable'] ) ? [ $rule['verifiable'], 'verify' ] : $rule['verify'];
			$get_weight_function = isset( $rule['verifiable'] ) ? [ $rule['verifiable'], 'get_weight' ] : $rule['get_weight'];
			$get_slug_function = isset( $rule['verifiable'] ) ? [ $rule['verifiable'], 'get_slug' ] : $rule['get_slug'];

			$rule_score = call_user_func( $verify_function, $item ) * call_user_func( $get_weight_function );
			if ( $rule_score > 0.0 ) {
				$this->spam_reasons[] = call_user_func( $get_slug_function );
			} else {
				$this->no_spam_reasons[] = call_user_func( $get_slug_function );
			}

			$score += $rule_score;

			$no_spam_threshold = (float) apply_filters( 'asb_no_spam_threshold', 0.0 );
			$spam_threshold = (float) apply_filters( 'asb_spam_threshold', 0.0 );
			if ( $no_spam_threshold < 0.0 && $score <= $no_spam_threshold ) {
				return false;
			}

			if ( $spam_threshold > 0.0 && $score >= $spam_threshold ) {
				return true;
			}
		}

		return $score > 0.0;
	}

	public static function get( $type = null, $only_active = false ) {
		$all_rules = apply_filters( 'asb_rules', [] );

		$rules = [];
		foreach ( $all_rules as $rule ) {
			if ( self::is_valid_rule( $rule ) ) {

				$get_supported_types_function = isset( $rule['verifiable'] ) ? [ $rule['verifiable'], 'get_supported_types' ] : $rule['get_supported_types'];
				$supported_types = call_user_func( $get_supported_types_function );

				if ( ! in_array( $type, $supported_types ) ) {
					continue;
				}

				if ( ! $only_active ) {
					$rules[] = $rule;
					continue;
				}

				$is_active_function = isset( $rule['verifiable'] ) ? [ $rule['verifiable'], 'is_active' ] : $rule['is_active'];
				$is_active = call_user_func( $is_active_function, $type );

				if ( ! $is_active ) {
					continue;
				}

				$rules[] = $rule;
			}
		}

		return $rules;
	}

	private static function is_valid_rule( $rule ) {
		if ( isset( $rule['verifiable'] ) ) {
			$interfaces = class_implements( $rule['verifiable'] );
			if ( false === $interfaces || empty( $interfaces ) ) {
				return false;
			}

			if ( ! in_array( Verifiable::class, $interfaces, true ) ) {
				return false;
			}

			return true;
		}

		$rule_callables = [
			'is_active',
			'verify',
			'get_slug',
			'get_supported_types',
		];

		foreach ( $rule_callables as $key ) {
			if ( ! isset( $rule[ $key ] ) || ! is_callable( $rule[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	public function get_spam_reasons() {
		return $this->spam_reasons;
	}

	public function get_no_spam_reasons() {
		return $this->no_spam_reasons;
	}
}
