<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Field;

class NullField implements FieldInterface
{

    public function type() : string
    {
        return '';
    }

    public function key() : string
    {
        return '';
    }

    public function value()
    {
        return null;
    }

    public function options() : array
    {
        return [];
    }

    public function label() : string
    {
        return '';
    }

    public function with_value( $value ) : FieldInterface
    {
        return $this;
    }
}
