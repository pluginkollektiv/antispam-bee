<?php
/**
 * A filter should either implement the NoSpamFilterInterface or the SpamFilterInterface. Depending on this
 * implementation, the reading of the result of filter() should be done.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Interface FilterInterface
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
interface FilterInterface {



	/**
	 * Filters the data and determines its value towards spam or no spam.
	 *
	 * @param DataInterface $data The data, to check for spam.
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float;

	/**
	 * Registers the filter.
	 *
	 * @return bool
	 */
	public function register() : bool;

	/**
	 * The options of the filter.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface;

	/**
	 * The ID of the filter.
	 *
	 * @return string
	 */
	public function id() : string;

	/**
	 * Returns whether a data object can be cheked.
	 *
	 * @param DataInterface $data The data to be checked.
	 *
	 * @return bool
	 */
	public function can_check_data( DataInterface $data) : bool;
}
