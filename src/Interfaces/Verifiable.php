<?php
/**
 * Verifiable interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

/**
 * Verifiable element interface.
 */
interface Verifiable {
	/**
	 * Verify an item.
	 * Applies logic and returns a numeric value, positive, negative or zero (neutral).
	 *
	 * Receives the normalized payload built by the reaction (see
	 * {@see \AntispamBee\Handlers\Reaction::build_payload()}), keyed by generic
	 * attributes (`reaction_type`, `ip`, `url`, `host`, `body`, `email`,
	 * `author`, `useragent`, `post_id`) rather than the raw reaction data.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Weighted result.
	 */
	public static function verify( array $item ): int;

	/**
	 * Get the rule weight.
	 * This value can be used to tweak the overall results. Will be used as a multiplier of the verification result.
	 *
	 * @return int Weight factor.
	 */
	public static function get_weight(): int;

	/**
	 * Get the element slug.
	 *
	 * @return string The slug.
	 */
	public static function get_slug(): string;

	/**
	 * Get a list of supported types.
	 *
	 * @return string[] A list of supported types.
	 */
	public static function get_supported_types(): array;

	/**
	 * Is this rule final?
	 *
	 * Final rules are checked before all other rules. If a final rule returns
	 * a positive result, the item is marked as spam right away and the
	 * remaining rules are not evaluated.
	 *
	 * @return bool Whether this rule is final.
	 */
	public static function is_final(): bool;
}
