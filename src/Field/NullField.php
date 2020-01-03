<?php
/**
 * The Null Field
 *
 * @package Antispam Bee Field
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

/**
 * Class NullField
 *
 * @package Pluginkollektiv\AntispamBee\Field
 */
class NullField implements FieldInterface {

	/**
	 * The type of the field.
	 *
	 * @return string
	 */
	public function type() : string {
		return '';
	}

	/**
	 * The key of the field.
	 *
	 * @return string
	 */
	public function key() : string {
		return '';
	}

	/**
	 * The value of the field.
	 *
	 * @return null
	 */
	public function value() {
		return null;
	}

	/**
	 * The options for the field.
	 *
	 * @return array
	 */
	public function options() : array {
		return [];
	}

	/**
	 * The label for the field.
	 *
	 * @return string
	 */
	public function label() : string {
		return '';
	}

	/**
	 * Returns the identical field.
	 *
	 * @param mixed $value Since its the NullField, the value will not be changed.
	 *
	 * @return FieldInterface
	 */
	public function with_value( $value ) : FieldInterface {
		return $this;
	}
}
