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
	 * @param array $item Item to verify.
	 * @return int Weighted result.
	 */
	public static function verify( array $item ): int;

	/**
	 * Get rule weight.
	 * This value can be used to tweak the overall results. Will be user as a multiplier of the verification result.
	 *
	 * @return int Weight factor.
	 */
	public static function get_weight(): int;

	/**
	 * Get element slug.
	 *
	 * @return string The slug.
	 */
	public static function get_slug(): string;

	/**
	 * Get a list of supported types.
	 *
	 * @return string[]
	 */
	public static function get_supported_types(): array;

	/**
	 * It this rule final?
	 *
	 * @return bool
	 */
	public static function is_final(): bool;
}
