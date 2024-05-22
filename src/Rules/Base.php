<?php
/**
 * Base Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\Verifiable;

/**
 * Abstract base class for rules.
 */
abstract class Base implements Verifiable {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug;

	/**
	 * Rule weight.
	 *
	 * @var int
	 */
	protected static $weight = 1;

	/**
	 * Is this rule final?
	 *
	 * @var bool
	 */
	protected static $is_final = false;

	/**
	 * Supported reaction types.
	 * Defaults to comments and linkbacks.
	 *
	 * @var array
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE, ContentTypeHelper::LINKBACK_TYPE ];

	/**
	 * Set to `true`, if the rule should not be displayed anywhere,
	 * like in the reasons list for the DeleteForReasons rule.
	 *
	 * @var bool
	 */
	protected static $is_invisible = false;

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'antispam_bee_rules', [ static::class, 'add_rule' ] );
	}

	/**
	 * Add the current rule class to the 'antispam_bee_rules' filter.
	 *
	 * @param array $rules The currently registered rules.
	 *
	 * @return array
	 */
	public static function add_rule( $rules ) {
		$rules[] = static::class;

		return $rules;
	}

	/**
	 * Returns the types for which this rule can be used.
	 *
	 * @return array
	 */
	public static function get_supported_types() {
		/**
		 * Filter the reaction types that are supported by the rule.
		 *
		 * @param array $supported_types The supported types.
		 * @param string $slug The rule’s slug.
		 *
		 * @return array Array of supported types.
		 */
		return apply_filters( 'antispam_bee_rule_supported_types', static::$supported_types, static::$slug );
	}

	/**
	 * Get rule weight.
	 * This value can be used to tweak the overall results. Will be user as a multiplier of the verification result.
	 *
	 * @return int Weight factor.
	 */
	public static function get_weight() {
		return static::$weight;
	}

	/**
	 * Get element slug.
	 *
	 * @return string The slug.
	 */
	public static function get_slug() {
		return static::$slug;
	}

	/**
	 * It this rule final?
	 *
	 * @return bool
	 */
	public static function is_final() {
		return static::$is_final;
	}

	/**
	 * It this rule invisible?
	 *
	 * @return bool
	 */
	public static function is_invisible() {
		return static::$is_invisible;
	}
}
