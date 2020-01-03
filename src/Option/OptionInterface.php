<?php
/**
 * The option interface.
 *
 * @package Antispam Bee Option
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Field\FieldInterface;

/**
 * Interface OptionInterface
 *
 * @package Pluginkollektiv\AntispamBee\Option
 */
interface OptionInterface {


	/**
	 * The name of the filter.
	 *
	 * @return string
	 */
	public function name() : string;

	/**
	 * The description of the filter.
	 *
	 * @return string
	 */
	public function description() : string;

	/**
	 * Whether you can activate/deactivate this filter through the settings.
	 *
	 * @return bool
	 */
	public function activateable() : bool;

	/**
	 * Specific setting fields.
	 *
	 * @return FieldInterface[]
	 */
	public function fields() : array;

	/**
	 * Has a specific setting field.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key) : bool;

	/**
	 * Value of a specific setting field.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	public function get( string $key);

	/**
	 * Sanitizes a value for a given key.
	 *
	 * @param mixed  $raw_value The value.
	 * @param string $key       The key.
	 *
	 * @return mixed
	 */
	public function sanitize( $raw_value, string $key );
}
