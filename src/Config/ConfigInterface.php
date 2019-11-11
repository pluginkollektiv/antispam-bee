<?php

namespace Pluginkollektiv\AntispamBee\Config;

interface ConfigInterface
{

    public function has( string $key) : bool;

    public function get( string $key);

    public function has_config( string $key ) : bool;

    public function get_config( string $key ) : ConfigInterface;

    public function set( string $key, $value) : bool;

    public function persist() : bool;
}
