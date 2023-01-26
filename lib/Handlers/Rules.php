<?php

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\Components;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\SpamReason;
use AntispamBee\Interfaces\Verifiable;

class Rules {
	protected $type;
	protected $spam_reasons    = [];
	protected $no_spam_reasons = [];

	public function __construct( $type ) {
		$this->type = $type;
	}

	public function apply( $item ) {
		$item['content_type'] = $this->type;
		$rules                = self::get( $this->type, true );

		$score = 0.0;
		foreach ( $rules as $rule ) {
			$rule_score = $rule::verify( $item ) * $rule::get_weight();

			if ( $rule_score > 0.0 ) {
				$this->spam_reasons[] = $rule::get_slug();
			} else {
				$this->no_spam_reasons[] = $rule::get_slug();
			}

			$score += $rule_score;

			$no_spam_threshold = (float) apply_filters( 'antispam_bee_no_spam_threshold', 0.0 );
			$spam_threshold    = (float) apply_filters( 'antispam_bee_spam_threshold', 0.0 );

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
		return self::filter( [
			'content_type' => $type,
			'only_active' => $only_active,
			'implements' => Verifiable::class,
		] );
	}

	public static function get_controllables( $type = null, $only_active = false ) {
		return self::filter( [
			'content_type' => $type,
			'only_active' => $only_active,
			'implements' => [ Verifiable::class, Controllable::class ],
		] );
	}

	// Todo: Try to find a better suited method name.
	public static function get_spam_rules( $type = null, $only_active = false ) {
		return self::filter( [
			'content_type' => $type,
			'only_active' => $only_active,
			'implements' => [ Verifiable::class, SpamReason::class ],
		] );
	}

	private static function filter( $options ) {
		// Todo: discuss if our rules should be filterable or not.
		return Components::filter( apply_filters( 'antispam_bee_rules', [] ), $options );
	}

	public function get_spam_reasons() {
		return $this->spam_reasons;
	}

	public function get_no_spam_reasons() {
		return $this->no_spam_reasons;
	}
}