<?php
/**
 * General Options base class.
 *
 * @package AntispamBee\GeneralOptions
 */

namespace AntispamBee\GeneralOptions;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;


/**
 * Abstract base class for general options.
 */
abstract class Base implements Controllable {

	/**
	 * Component type.
	 *
	 * @var string
	 */
	protected static $component_type = 'general';

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	protected static $slug;

	/**
	 * Only show custom options?
	 *
	 * @var bool
	 */
	protected static $only_custom_options = false;

	/**
	 * Get the options.
	 *
	 * {@inheritDoc}
	 *
	 * @return array|null The option data, or null.
	 */
	public static function get_options(): ?array {
		return null;
	}

	/**
	 * Add setting to the list of general options.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'antispam_bee_general_options', [ static::class, 'add_general_option' ] );
	}

	/**
	 * Add setting to general options.
	 *
	 * @param array $options Currently registered options.
	 *
	 * @return array Updated options.
	 */
	public static function add_general_option( array $options ): array {
		$options[] = static::class;

		return $options;
	}

	/**
	 * Return activation state for this option.
	 *
	 * @param string $reaction_type One of the supported reaction types (comment, linkback, general).
	 *
	 * @return mixed|null The activation state, or null if unset.
	 */
	public static function is_active( string $reaction_type = 'general' ) {
		return Settings::get_option( static::get_option_name( 'active' ), $reaction_type );
	}

	/**
	 * Get the option name.
	 * Append type and slug to the given name.
	 *
	 * @param string $name Name suffix.
	 *
	 * @return string Corresponding option name.
	 */
	public static function get_option_name( string $name ): string {
		$component_type = static::get_component_type();
		$slug           = static::get_slug();
		$option_name    = "{$component_type}_{$slug}_{$name}";

		return str_replace( '-', '_', $option_name );
	}

	/**
	 * Get the component type (rule, post_processor, or general).
	 *
	 * @return string The component type.
	 */
	public static function get_component_type(): string {
		return static::$component_type;
	}

	/**
	 * Get the option slug.
	 *
	 * @return string The option slug.
	 */
	public static function get_slug(): string {
		return static::$slug;
	}

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool Whether only custom options are printed.
	 */
	public static function only_print_custom_options(): bool {
		return static::$only_custom_options;
	}

	/**
	 * Get a list of supported types.
	 *
	 * @return string[] The supported types.
	 */
	public static function get_supported_types(): array {
		return [ 'general' ];
	}
}
