<?php
/**
 * The text field.
 *
 * @package Antispam Bee Field
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

/**
 * Class TextField
 *
 * @package Pluginkollektiv\AntispamBee\Field
 */
class TextField implements FieldInterface {

	/**
	 * The key of the field.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * The value of the field.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * The label of the field.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * TextField constructor.
	 *
	 * @param string $key The key of the field.
	 * @param string $value The value of the field.
	 * @param string $label The label of the field.
	 */
	public function __construct( string $key, string $value, string $label ) {
		$this->key   = $key;
		$this->value = $value;
		$this->label = $label;

	}

	/**
	 * The type of the field.
	 *
	 * @return string
	 */
	public function type() : string {
		return 'text';
	}

	/**
	 * The key of the field.
	 *
	 * @return string
	 */
	public function key() : string {
		return $this->key;
	}

	/**
	 * The value of the field.
	 *
	 * @return mixed
	 */
	public function value() {
		return $this->value;
	}

	/**
	 * The field options.
	 *
	 * @return array
	 */
	public function options() : array {
		return [];
	}

	/**
	 * The label of the field.
	 *
	 * @return string
	 */
	public function label() : string {
		return $this->label;
	}

	/**
	 * Returns the same FieldInterface with an updated value.
	 *
	 * @param mixed $value The new value.
	 *
	 * @return FieldInterface
	 */
	public function with_value( $value ) : FieldInterface {
		$this->value = $value;
		return $this;
	}
}
