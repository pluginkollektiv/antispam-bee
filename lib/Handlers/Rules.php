<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\InterfaceHelper;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;
use AntispamBee\Rules\ApprovedEmail;

class Rules {
	protected $type;
	protected $spam_reasons    = [];
	protected $no_spam_reasons = [];

	public function __construct( $type ) {
		$this->type = $type;
	}

	public function apply( $item ) {
		$item['asb_item_type'] = $this->type;
		$rules                 = self::get( $this->type, true );

		$score = 0.0;
		foreach ( $rules as $rule ) {
			$rule_score = InterfaceHelper::call( $rule, 'verifiable', 'verify', $item )
			              * InterfaceHelper::call( $rule, 'verifiable', 'get_weight' );

			if ( $rule_score > 0.0 ) {
				$this->spam_reasons[] = InterfaceHelper::call( $rule, 'verifiable', 'get_slug' );
			} else {
				$this->no_spam_reasons[] = InterfaceHelper::call( $rule, 'verifiable', 'get_slug' );
			}

			$score += $rule_score;

			$no_spam_threshold = (float) apply_filters( 'asb_no_spam_threshold', 0.0 );
			$spam_threshold    = (float) apply_filters( 'asb_spam_threshold', 0.0 );
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
		return self::filter( apply_filters( 'asb_rules', [] ), [
			'type' => $type,
			'only_active' => $only_active,
		] );
	}

	private static function is_valid_rule( $rule ) {
		if ( isset( $rule['verifiable'] ) ) {
			return InterfaceHelper::conforms_to_interface( $rule['verifiable'], Verifiable::class );
		}

		return false;
	}

	public function get_spam_reasons() {
		return $this->spam_reasons;
	}

	public function get_no_spam_reasons() {
		return $this->no_spam_reasons;
	}

	/**
	 * @param $options
	 * $options = array(
	 *   'type'            => 'comment',
	 *   'only_active'       => true,
	 *   'is_controllable' => false,
	 * );
	 */
	public static function filter( $rules, $options ) {
		// Todo: Fix: The name mustn't break the options page, we should sort out everything that can break the plugin.
		// Todo: Check if other things can break it too

		$type = isset( $options['type'] ) ? $options['type'] : null;
		$only_active = isset( $options['only_active'] ) ? $options['only_active'] : false;
		// Todo: Change the key from is_controllable to only_controllables
		$only_controllables = isset( $options['is_controllable'] ) ? $options['is_controllable'] : false;

		$filtered_rules = [];
		foreach ( $rules as $rule ) {
			if ( self::is_valid_rule( $rule ) ) {
				$supported_types = InterfaceHelper::call( $rule, 'verifiable', 'get_supported_types' );
				if ( ! is_null( $type ) && ! in_array( $type, $supported_types ) ) {
					continue;
				}

				$conforms_to_controllable = isset( $rule['controllable'] ) ?
					InterfaceHelper::conforms_to_interface( $rule['controllable'], Controllable::class ) : false;
				if ( $only_controllables && ! $conforms_to_controllable ) {
					continue;
				}

				if ( $only_active ) {
					if ( $conforms_to_controllable && ! InterfaceHelper::call( $rule, 'verifiable', 'is_active', $type ) ) {
						continue;
					}
				}

				$filtered_rules[] = $rule;
			}
		}

		return $filtered_rules;
	}
}
