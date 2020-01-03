<?php
/**
 * The generic option object.
 *
 * @package Antispam Bee Option
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Field\FieldInterface;

/**
 * Class GenericOption
 *
 * @package Pluginkollektiv\AntispamBee\Option
 */
class GenericOption implements OptionInterface {

	/**
	 * The name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * The fields.
	 *
	 * @var FieldInterface[]
	 */
	private $fields = [];

	/**
	 * Whether the filter/post processor can be (de-)activated or not.
	 *
	 * @var bool
	 */
	private $activateable;

	/**
	 * GenericOption constructor.
	 *
	 * @param string         $name The name of the filter/post processor.
	 * @param string         $description The description of the filter/post processor.
	 * @param bool           $activateable Whether the filter/post processor can be activated or not.
	 * @param FieldInterface ...$fields The fields for this filter/post processor.
	 */
	public function __construct( string $name, string $description, bool $activateable, FieldInterface ...$fields ) {
		$this->name        = $name;
		$this->description = $description;
		foreach ( $fields as $field ) {
			$this->fields[ $field->key() ] = $field;
		}
		$this->activateable = $activateable;
	}


	/**
	 * Specific setting fields.
	 *
	 * @return FieldInterface[]
	 */
	public function fields() : array {
		return $this->fields;
	}

	/**
	 * Whether you can activate/deactivate this filter through the settings.
	 *
	 * @return bool
	 */
	public function activateable() : bool {
		return $this->activateable;
	}

	/**
	 * The name of the filter.
	 *
	 * @return string
	 */
	public function name() : string {
		return $this->name;
	}

	/**
	 * The description of the filter.
	 *
	 * @return string
	 */
	public function description() : string {
		return $this->description;
	}

	/**
	 * Has a specific setting field.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key ) : bool {
		return array_key_exists( $key, $this->fields );
	}

	/**
	 * Value of a specific setting field.
	 *
	 * @param string $key The key.
	 * @throws Runtime When the field does not exist.
	 *
	 * @return mixed
	 */
	public function get( string $key ) {

		if ( ! isset( $this->fields[ $key ] ) ) {
			throw new Runtime( "Field $key does not exist." );
		}
		return $this->fields[ $key ]->value();
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

		return sanitize_text_field( $raw_value );
	}
}
