<?php
/**
 * The Settings field interface
 *
 * @package Antispam Bee Field
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

/**
 * Interface FieldInterface
 *
 * @package Pluginkollektiv\AntispamBee\Field
 */
interface FieldInterface {

	/**
	 * The label of the field.
	 *
	 * @return string
	 */
	public function label(): string;

	/**
	 * The type of the field.
	 *
	 * @return string
	 */
	public function type() : string;

	/**
	 * The key of the field.
	 *
	 * @return string
	 */
	public function key() : string;

	/**
	 * The value of the field.
	 *
	 * @return mixed
	 */
	public function value();

	/**
	 * The field options.
	 *
	 * @return array
	 */
	public function options() : array;

	/**
	 * Returns the same FieldInterface with an updated value.
	 *
	 * @param mixed $value The new value.
	 *
	 * @return FieldInterface
	 */
	public function with_value( $value ) : FieldInterface;
}
