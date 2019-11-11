<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

class TextField implements FieldInterface
{

    private $key;
    private $value;
    private $label;
    public function __construct( string $key, string $value, string $label )
    {
        $this->key   = $key;
        $this->value = $value;
        $this->label = $label;

    }

    public function type() : string
    {
        return 'text';
    }

    public function key() : string
    {
        return $this->key;
    }

    public function value()
    {
        return $this->value;
    }

    public function options() : array
    {
        return [];
    }

    public function label() : string
    {
        return $this->label;
    }

    public function with_value( $value ) : FieldInterface
    {
        $this->value = $value;
        return $this;
    }
}
