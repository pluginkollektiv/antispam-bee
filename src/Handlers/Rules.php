<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Helpers\DebugMode;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\SpamReason;
use AntispamBee\Interfaces\Verifiable;

class Rules {
	protected $type;
	protected $spam_reasons = [];
	protected $no_spam_reasons = [];

	public function __construct( $type ) {
		$this->type = $type;
	}

	public function apply( $item ) {
		$item['reaction_type'] = $this->type;
		$rules                = self::get( $this->type, true );

		$no_spam_threshold = (float) apply_filters( 'antispam_bee_no_spam_threshold', 0.0 );
		$spam_threshold    = (float) apply_filters( 'antispam_bee_spam_threshold', 0.0 );

		$score = 0.0;

		$log_item = $item;
		unset( $log_item['comment_author_email'] );
		unset( $log_item['comment_author_IP'] );
		unset( $log_item['user_id'] );
		unset( $log_item['user_ID'] );
		DebugMode::log( 'Looping through spam rules for reaction with the following data: ' . print_r( $log_item, true ) );

		foreach ( $rules as $rule ) {
			DebugMode::log( "Checking »{$rule::get_name()}« rule" );

			$rule_score = $rule::verify( $item ) * $rule::get_weight();
			
			DebugMode::log( "Score: {$rule_score}" );

			if ( $rule_score > 0.0 ) {
				$this->spam_reasons[] = $rule::get_slug();
			} else {
				$this->no_spam_reasons[] = $rule::get_slug();
			}

			$score += $rule_score;

			DebugMode::log( "Overall score after checking the rule: {$score}" );
		}

		DebugMode::log( "Overall score after checking all rules: {$score}" );

		if ( $no_spam_threshold < 0.0 && $score <= $no_spam_threshold ) {
			return false;
		}

		if ( $spam_threshold > 0.0 && $score >= $spam_threshold ) {
			return true;
		}

		return $score > 0.0;
	}

	public static function get( $type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $type,
				'only_active'   => $only_active,
				'implements'    => Verifiable::class,
			]
		);
	}

	public static function get_controllables( $type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $type,
				'only_active'   => $only_active,
				'implements'    => [ Verifiable::class, Controllable::class ],
			]
		);
	}

	// Todo: Try to find a better suited method name.
	public static function get_spam_rules( $type = null, $only_active = false ) {
		return self::filter(
			[
				'reaction_type' => $type,
				'only_active'   => $only_active,
				'implements'    => [ Verifiable::class, SpamReason::class ],
			]
		);
	}

	private static function filter( $options ) {
		// Todo: discuss if our rules should be filterable or not.
		return ComponentsHelper::filter( apply_filters( 'antispam_bee_rules', [] ), $options );
	}

	public function get_spam_reasons() {
		return $this->spam_reasons;
	}

	public function get_no_spam_reasons() {
		return $this->no_spam_reasons;
	}
}
