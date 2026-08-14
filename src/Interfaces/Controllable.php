<?php
/**
 * Controllables interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

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
	 * First thoughts on how a rule can specify what kind of advanced option it is. If `type` is no callable,
	 * ASB takes care of rendering the option. If it is a callable, the rule has to take care of rendering, loading
	 * the data, saving.
	 * [
	 *   [
	 *     'type' => 'textarea|radio|checkbox|input|select|callable',
	 *       'input_type' => 'email|password|number...',
	 *     *   'label' => 'asb_deny_langcodes',
	 *       'option_name' => 'asb_deny_langcodes',
	 *       'options' => [ [ 'value' => 1, 'label' => 'Option 1' ], [ 'value' => 2, 'label' => 'Option 2' ] ],
	 *       'multiple' => true,
	 *       'placeholder' => 'My placeholder text',
	 *       'default' => 'Default value',
	 *       'sanitize' => callable,
	 *       'persist' => callable,
	 *       'load' => callable,
	 *   ]
	 * ]
	 *
	 * A `sanitize` callback is handed the posted value exactly as it arrived, so it
	 * has to accept `mixed`. Nothing guarantees the shape a request submits — a
	 * textarea can be posted as an array — and a callback that declares a narrower
	 * parameter type turns that into a `TypeError` instead of a rejected value.
	 * Returning `null` removes the option, so discarding an unusable value is
	 * always possible.
	 *
	 * @return array<int, array<string, mixed>>|null A list of advanced options, or null.
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
