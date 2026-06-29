<?php
/**
 * Controllable Base Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\Settings;
use AntispamBee\Interfaces\Controllable;

/**
 * Abstract base class for controllable rules.
 */
abstract class ControllableBase extends Base implements Controllable {

	/**
	 * Only print custom options?
	 *
	 * @var bool
	 */
	protected static $only_print_custom_options = false;

	/**
	 * Component type.
	 *
	 * @var string
	 */
	protected static $component_type = 'rule';

	/**
	 * Returns activation state of this rule.
	 *
	 * @param string $reaction_type One of the supported reaction types (comment, linkback, general).
	 *
	 * @return mixed|null
	 */
	public static function is_active( string $reaction_type ) {
		return Settings::get_option( static::get_option_name( 'active' ), $reaction_type );
	}

	/**
	 * Get post processor options.
	 *
	 * {@inheritDoc} Default: none.
	 *
	 * @return array|null
	 */
	public static function get_options(): ?array {
		return null;
	}

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool
	 */
	public static function only_print_custom_options(): bool {
		return static::$only_print_custom_options;
	}

	/**
	 * Get the component type (rule, post_processor or general).
	 *
	 * @return string
	 */
	public static function get_component_type(): string {
		return static::$component_type;
	}

	/**
	 * Get option name.
	 * This will add type and slug prefixes to the short name.
	 *
	 * @param string $name Name suffix.
	 * @return string Corresponding option name
	 */
	public static function get_option_name( string $name ): string {
		$component_type = static::get_component_type();
		$slug           = static::get_slug();
		$option_name    = "{$component_type}_{$slug}_{$name}";

		return str_replace( '-', '_', $option_name );
	}
}
