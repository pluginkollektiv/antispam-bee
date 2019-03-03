<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

interface FieldInterface {

	public function label(): string;

	public function type() : string;

	public function key() : string;

	public function value();

	public function options() : array;

	public function with_value( $value ) : FieldInterface;
}