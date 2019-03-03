<?php
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Handler;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;

interface SpamHandlerInterface {


	public function execute( string $reason, DataInterface $data) : bool;
}