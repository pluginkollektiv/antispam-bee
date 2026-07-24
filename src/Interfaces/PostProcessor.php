<?php
/**
 * PostProcessor interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

/**
 * Post-processor interface.
 */
interface PostProcessor {

	/**
	 * Process an item.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
	 */
	public static function process( array $item ): array;

	/**
	 * Get the post-processor slug.
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
	 * Does this processor mark an element as deleted?
	 *
	 * @return bool Whether this processor marks an element as deleted.
	 */
	public static function marks_as_delete(): bool;
}
