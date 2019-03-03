<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Option;

use Pluginkollektiv\AntispamBee\Config\ConfigInterface;
use Pluginkollektiv\AntispamBee\Field\FieldInterface;
use Pluginkollektiv\AntispamBee\Field\NullField;
use Pluginkollektiv\AntispamBee\Field\TextField;

class OptionFactory {

	private $config;

	public function __construct( ConfigInterface $config = null ) {
		$this->config = $config;
	}

	public function from_args( array $args ) : OptionInterface {
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

	private function cast_fields( $key, $definitions ) : FieldInterface {
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

	public function null() {
		return new NullOption();
	}
}
