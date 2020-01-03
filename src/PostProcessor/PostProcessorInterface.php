<?php
/**
 * The post processor interface.
 *
 * @package Antispam Bee PostProcessor
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Interface PostProcessorInterface
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
interface PostProcessorInterface {

	/**
	 * Executes the processor.
	 *
	 * @param ReasonsRepository $reason The reasons why the current data is spam.
	 * @param DataInterface     $data The current data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data) : bool;

	/**
	 * The ID of the post processor.
	 *
	 * @return string
	 */
	public function id() : string;

	/**
	 * Registers the post processor.
	 *
	 * @return bool
	 */
	public function register() : bool;

	/**
	 * Returns the options for this post processor.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface;
}
