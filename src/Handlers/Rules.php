<?php
/**
 * Rules.
 *
 * @package AntispamBee\Handlers
 */

namespace AntispamBee\Handlers;

use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Helpers\DebugMode;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\SpamReason;
use AntispamBee\Interfaces\Verifiable;

/**
 * Rules.
 */
class Rules {

	/**
	 * Reaction type.
	 *
	 * @var string
	 */
	protected $reaction_type;

	/**
	 * List of spam reasons.
	 *
	 * @var array
	 */
	protected $spam_reasons = [];

	/**
	 * List of no-spam reasons.
	 *
	 * @var array
	 */
	protected $no_spam_reasons = [];

	/**
	 * Ruleset constructor.
	 *
	 * @param string $reaction_type Reaction type.
	 */
	public function __construct( string $reaction_type ) {
		$this->reaction_type = $reaction_type;
	}

	/**
	 * Apply rules.
	 *
	 * @param array $item Item to apply rules to.
	 * @return bool Item identified as spam.
	 */
	public function apply( array $item ): bool {
		$item['reaction_type'] = $this->reaction_type;
		$rules                 = self::get( $this->reaction_type, true );

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

	/**
	 * Get applicable rules.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active rules.
	 * @return array List of applicable rules.
	 */
	public static function get( ?string $reaction_type = null, bool $only_active = false ): array {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => Verifiable::class,
			]
		);
	}

	/**
	 * Get controllable items.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active items.
	 * @return array List of suitable controllables.
	 */
	public static function get_controllables( ?string $reaction_type = null, bool $only_active = false ): array {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => [ Verifiable::class, Controllable::class ],
			]
		);
	}

	/**
	 * Get rules that provide a spam reason (implement the SpamReason interface).
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active rules.
	 * @return array List of rules that provide a spam reason.
	 */
	public static function get_spam_reason_rules( ?string $reaction_type = null, bool $only_active = false ): array {
		return self::filter(
			[
				'reaction_type' => $reaction_type,
				'only_active'   => $only_active,
				'implements'    => [ Verifiable::class, SpamReason::class ],
			]
		);
	}

	/**
	 * Filter items.
	 *
	 * @param array $options Filter options.
	 * @return array List of filtered elements.
	 */
	private static function filter( array $options ): array {
		return ComponentsHelper::filter( apply_filters( 'antispam_bee_rules', [] ), $options );
	}

	/**
	 * Get spam reasons.
	 *
	 * @return array
	 */
	public function get_spam_reasons(): array {
		return $this->spam_reasons;
	}

	/**
	 * Get no-spam reasons.
	 *
	 * @return array
	 */
	public function get_no_spam_reasons(): array {
		return $this->no_spam_reasons;
	}
}
