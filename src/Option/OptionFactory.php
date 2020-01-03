<?php
/**
 * Generates GenericOption objects from arguments.
 *
 * @package Antispam Bee Option
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Config\ConfigInterface;
use Pluginkollektiv\AntispamBee\Field\FieldInterface;
use Pluginkollektiv\AntispamBee\Field\NullField;
use Pluginkollektiv\AntispamBee\Field\TextField;

/**
 * Class OptionFactory
 *
 * @package Pluginkollektiv\AntispamBee\Option
 */
class OptionFactory {

	/**
	 * The configuration.
	 *
	 * @var ConfigInterface|null
	 */
	private $config;

	/**
	 * OptionFactory constructor.
	 *
	 * @param ConfigInterface|null $config The configuration.
	 */
	public function __construct( ConfigInterface $config = null ) {
		$this->config = $config;
	}

	/**
	 * Returns a GenericOption from arguments.
	 *
	 * @param array $args The arguments.
	 *                  activateable Whether this filter/post processor can be activated and deactivated
	 *                  fields The fields of the filter/post processor.
	 *                  name The name of the filter/post processor
	 *                  description The description of the filter/post processor.
	 *
	 * @return GenericOption
	 */
	public function from_args( array $args ) : GenericOption {
		$activateable = isset( $args['activateable'] ) ? $args['activateable'] : true;
		$fields       = isset( $args['fields'] ) ? $args['fields'] : [];
		$fields       = array_map(
			[
				$this,
				'cast_fields',
			],
			array_keys( $fields ),
			array_values( $fields )
		);
		return new GenericOption(
			$args['name'],
			$args['description'],
			$activateable,
			...$fields
		);
	}

	/**
	 * Cast definitions to field objects.
	 *
	 * @param string $key The key of the field.
	 * @param array  $definitions The field definitions.
	 *
	 * @return FieldInterface
	 */
	private function cast_fields( string $key, array $definitions ) : FieldInterface {
		switch ( $definitions['type'] ) {
			case 'text':
				$value = ( $this->config && $this->config->has( $key ) ) ? $this->config->get( $key ) : '';
				$field = new TextField( $key, $value, $definitions['label'] );
				break;
			default:
				$field = new NullField();
		}

		return $field;
	}

	/**
	 * Returns the NullOption
	 *
	 * @return NullOption
	 */
	public function null() {
		return new NullOption();
	}
}
