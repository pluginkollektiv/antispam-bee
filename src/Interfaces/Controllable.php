<?php
/**
 * Controllables interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

use AntispamBee\Admin\Fields\FieldOptions;

/**
 * Common interface for controllable elements.
 * This can be options, processors, or rules.
 */
interface Controllable {

	/**
	 * Get the element name.
	 *
	 * @return string The element name.
	 */
	public static function get_name(): string;

	/**
	 * Get the element label (optional).
	 *
	 * @return string|null The element label, or null.
	 */
	public static function get_label(): ?string;

	/**
	 * Get the element description (optional).
	 *
	 * @return string|null The element description, or null.
	 */
	public static function get_description(): ?string;

	/**
	 * Get the advanced options for this element.
	 *
	 * Each option is a {@see FieldOptions} value object that the admin UI
	 * renders. Build them via the {@see FieldBuilder} factory so the available
	 * keys stay typed and discoverable.
	 *
	 * A `sanitize` callback is handed the posted value exactly as it arrived, so it
	 * has to accept `mixed`. Nothing guarantees the shape a request submits — a
	 * textarea can be posted as an array — and a callback that declares a narrower
	 * parameter type turns that into a `TypeError` instead of a rejected value.
	 * Returning `null` removes the option, so discarding an unusable value is
	 * always possible.
	 *
	 * @return array<int, FieldOptions>|null A list of advanced options, or null.
	 */
	public static function get_options(): ?array;


	/**
	 * Return activation state for this element.
	 *
	 * @param string $reaction_type One of the supported reaction types (comment, linkback, general).
	 *
	 * @return mixed|null The activation state for the given reaction type.
	 */
	public static function is_active( string $reaction_type );

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool Whether only custom options should be printed.
	 */
	public static function only_print_custom_options(): bool;

	/**
	 * Get a list of supported types.
	 *
	 * @return string[] A list of supported types.
	 */
	public static function get_supported_types(): array;

	/**
	 * Get the component type (rule, post_processor, or general).
	 *
	 * @return string The component type (rule, post_processor, or general).
	 */
	public static function get_component_type(): string;

	/**
	 * Get the controllable slug.
	 *
	 * @return string The controllable slug.
	 */
	public static function get_slug(): string;

	/**
	 * Get the option name.
	 * This will typically add the component type and slug prefixes to the short name.
	 *
	 * @param string $name Name suffix.
	 *
	 * @return string Corresponding option name.
	 */
	public static function get_option_name( string $name ): string;
}
