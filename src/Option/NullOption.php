<?php
/**
 * The null option.
 *
 * @package Antispam Bee Option
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;

/**
 * Class NullOption
 *
 * @package Pluginkollektiv\AntispamBee\Option
 */
class NullOption implements OptionInterface {

	/**
	 * The name of the filter.
	 *
	 * @return string
	 */
	public function name() : string {
		return '';
	}

	/**
	 * The description of the filter.
	 *
	 * @return string
	 */
	public function description() : string {
		return '';
	}

	/**
	 * Whether you can activate/deactivate this filter through the settings.
	 *
	 * @return bool
	 */
	public function activateable() : bool {
		return false;
	}

	/**
	 * Specific setting fields.
	 *
	 * @return FieldInterface[]
	 */
	public function fields() : array {
		return [];
	}

	/**
	 * Has a specific setting field.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key ) : bool {
		return false;
	}

	/**
	 * Value of a specific setting field.
	 *
	 * @param string $key The key.
	 * @throws Runtime Because there is nothing to get from the NullOption.
	 */
	public function get( string $key ) {
		throw new Runtime( "The field $key is not registered in the NullOption." );
	}

	/**
	 * Sanitizes a value for a given key.
	 *
	 * @param mixed  $raw_value The value.
	 * @param string $key       The key.
	 *
	 * @return mixed
	 */
	public function sanitize( $raw_value, string $key ) {
		return null;
	}
}
