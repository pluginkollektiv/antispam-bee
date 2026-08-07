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
use ReflectionException;

/**
 * Rules.
 *
 * @internal Third-party code should use {@see \AntispamBee\Api\SpamCheck} instead.
 *           This class is an implementation detail and may change in any release.
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
	 * @var string[]
	 */
	protected $spam_reasons = [];

	/**
	 * List of no-spam reasons.
	 *
	 * @var string[]
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
	 * Get the controllable items.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active items.
	 *
	 * @return array<class-string<Controllable>> A list of suitable controllables.
	 * @throws ReflectionException
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
	 * Filter items.
	 *
	 * @param array<string, mixed> $options Filter options.
	 *
	 * @return array<class-string<Controllable>> A list of filtered elements.
	 * @throws ReflectionException
	 */
	private static function filter( array $options ): array {
		/**
		 * Filters the registered rules.
		 *
		 * Rules are the checks Antispam Bee runs against a reaction to classify it
		 * as spam or ham. Add or remove fully-qualified class names to change which
		 * rules are available.
		 *
		 * @since 3.0.0
		 *
		 * @param array $rules A list of rule class names.
		 */
		$rules = apply_filters( 'antispam_bee_rules', [] );

		return ComponentsHelper::filter( $rules, $options );
	}

	/**
	 * Get the rules that provide a spam reason (implement the SpamReason interface).
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active rules.
	 *
	 * @return array<class-string> A list of rules that provide a spam reason.
	 * @throws ReflectionException
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
	 * Apply rules.
	 *
	 * Final rules are checked before all other rules. A positive result of a
	 * final rule is definitive: the item is marked as spam without evaluating
	 * the remaining (potentially expensive) rules.
	 *
	 * @param array<string, mixed> $item Normalized payload to apply rules to.
	 *
	 * @return bool Whether the item was identified as spam.
	 * @throws ReflectionException
	 */
	public function apply( array $item ): bool {
		$rules = self::get( $this->reaction_type, true );

		if ( empty( $rules ) ) {
			DebugMode::log( "No active rule for reaction type {$this->reaction_type}, the item was not checked." );
		}

		/**
		 * Filters the score threshold below which a reaction is considered no spam.
		 *
		 * A reaction whose accumulated rule score is lower than or equal to this
		 * threshold is treated as ham, regardless of the individual rule results.
		 *
		 * @since 3.0.0
		 *
		 * @param float $no_spam_threshold The no-spam score threshold. Default 0.0.
		 */
		$no_spam_threshold = (float) apply_filters( 'antispam_bee_no_spam_threshold', 0.0 );

		/**
		 * Filters the score threshold above which a reaction is considered spam.
		 *
		 * A reaction whose accumulated rule score is higher than or equal to this
		 * threshold is treated as spam.
		 *
		 * @since 3.0.0
		 *
		 * @param float $spam_threshold The spam score threshold. Default 0.0.
		 */
		$spam_threshold = (float) apply_filters( 'antispam_bee_spam_threshold', 0.0 );

		$score = 0.0;

		/**
		 * Filters the payload attributes that are anonymized (removed) before the
		 * payload is written to the debug log.
		 *
		 * @param string[]             $attributes Attribute keys to remove from the log entry.
		 * @param array<string, mixed> $item       The normalized payload (includes `reaction_type`).
		 */
		$anonymized_attributes = (array) apply_filters( 'antispam_bee_log_anonymized_attributes', [ 'ip', 'email' ], $item );

		$log_item = array_diff_key( $item, array_flip( $anonymized_attributes ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		DebugMode::log( 'Looping through spam rules for reaction with the following data: ' . print_r( $log_item, true ) );

		foreach ( $this->sort_rules( $rules ) as $rule ) {
			DebugMode::log( "Checking »{$rule::get_name()}« rule" );

			$rule_score = $rule::verify( $item ) * $rule::get_weight();

			DebugMode::log( "Score: {$rule_score}" );

			if ( $rule_score > 0.0 ) {
				$this->spam_reasons[] = $rule::get_slug();

				if ( $rule::is_final() ) {
					DebugMode::log( "»{$rule::get_name()}« is a final rule with a positive score — marking as spam without checking the remaining rules" );

					return true;
				}
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
	 * Sort the rules so that final rules are checked first.
	 *
	 * The sorting can be disabled with the `antispam_bee_sort_final_rules_first`
	 * filter, in which case the rules are checked in their registered order.
	 *
	 * @param array<class-string> $rules Rules to sort.
	 *
	 * @return array<class-string> Sorted rules.
	 */
	private function sort_rules( array $rules ): array {
		/**
		 * Filter whether final rules should be checked before all other rules.
		 *
		 * @since 3.0.0
		 *
		 * @param bool   $sort_final_first Whether to check final rules first. Default true.
		 * @param string $reaction_type    The reaction type (e.g. "comment").
		 */
		$sort_final_first = (bool) apply_filters( 'antispam_bee_sort_final_rules_first', true, $this->reaction_type );

		if ( ! $sort_final_first ) {
			return $rules;
		}

		$final_rules     = [];
		$non_final_rules = [];
		foreach ( $rules as $rule ) {
			if ( $rule::is_final() ) {
				$final_rules[] = $rule;
			} else {
				$non_final_rules[] = $rule;
			}
		}

		return array_merge( $final_rules, $non_final_rules );
	}

	/**
	 * Get the applicable rules.
	 *
	 * @param string|null $reaction_type Reaction type.
	 * @param bool        $only_active   Get only active rules.
	 *
	 * @return array<class-string> A list of applicable rules.
	 * @throws ReflectionException
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
	 * Get the spam reasons.
	 *
	 * @return string[] The spam reasons.
	 */
	public function get_spam_reasons(): array {
		return $this->spam_reasons;
	}

	/**
	 * Get the no-spam reasons.
	 *
	 * @return string[] The no-spam reasons.
	 */
	public function get_no_spam_reasons(): array {
		return $this->no_spam_reasons;
	}
}
