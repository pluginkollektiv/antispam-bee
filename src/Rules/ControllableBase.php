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
	 * Type of the controllable item.
	 *
	 * @var string
	 */
	protected static $type = 'rule';

	/**
	 * Returns activation state of this rule.
	 *
	 * @param string $type One of the types defined in AntispamBee\Helpers\ItemTypeHelper::get_types().
	 *
	 * @return mixed|null
	 */
	public static function is_active( string $type ) {
		return Settings::get_option( static::get_option_name( 'active' ), $type );
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
	 * Get type of the controllable.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return static::$type;
	}

	/**
	 * Get option name.
	 * This will add type and slug prefixes to the short name.
	 *
	 * @param string $name Name suffix.
	 * @return string Corresponding option name
	 */
	public static function get_option_name( string $name ): string {
		$type        = static::get_type();
		$slug        = static::get_slug();
		$option_name = "{$type}_{$slug}_{$name}";

		return str_replace( '-', '_', $option_name );
	}
}