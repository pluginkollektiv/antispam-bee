<?php
/**
 * NullFilter.
 *
 * This filter won't help you a lot.
 *
 * @package Antispam Bee Filter
 */
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\NullOption;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Class NullFilter
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class NullFilter implements FilterInterface {

	/**
	 * @var NullOption $options
	 */
	private $options;

	/**
	 * NullFilter constructor.
	 *
	 * @param NullOption $options
	 */
	public function __construct( NullOption $options ) {
		$this->options = $options;
	}

	/**
	 * Won't filter anything.
	 *
	 * @param DataInterface $data
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float {
		return 0;
	}

	/**
	 * Won't register anything.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * Returns a NullOption.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		return $this->options;
	}

	/**
	 * Returns the ID.
	 *
	 * @return string
	 */
	public function id() : string {
		return '';
	}
}
