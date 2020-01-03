<?php
/**
 * The spam handler interface.
 *
 * @package Antispam Bee Handler
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Handler;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Interface SpamHandlerInterface
 *
 * @package Pluginkollektiv\AntispamBee\Handler
 */
interface SpamHandlerInterface {

	/**
	 * Once a spam has been detected, this method will handle the rest.
	 *
	 * @param ReasonsRepository $reason The spam reason.
	 * @param DataInterface     $data   The spam data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data) : bool;
}
