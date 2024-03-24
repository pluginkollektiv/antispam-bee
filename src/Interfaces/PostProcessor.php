<?php
/**
 * PostProcessor interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

interface PostProcessor {

	/**
	 * Process an item.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( array $item ): array;

	/**
	 * Get post processor slug.
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
	 * Does this processor mark am element as deleted?
	 *
	 * @return bool
	 */
	public static function marks_as_delete(): bool;
}
