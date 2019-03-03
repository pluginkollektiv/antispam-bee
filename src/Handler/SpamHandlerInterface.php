<?php
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Handler;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

interface SpamHandlerInterface {


	public function execute( ReasonsRepository $reason, DataInterface $data) : bool;
}
