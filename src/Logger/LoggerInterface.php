<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Logger;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;

interface LoggerInterface
{

    public function log( string $entry) : bool;

    public function is_ready() : bool;
}
