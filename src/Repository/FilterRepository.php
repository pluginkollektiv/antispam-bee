<?php
/**
 * The filter repository.
 *
 * @package Antispam Bee Repository
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Repository;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\Filter\NoSpamFilterInterface;
use Pluginkollektiv\AntispamBee\Filter\SpamFilterInterface;

/**
 * Class CheckRepository
 *
 * @package Pluginkollektiv\AntispamBee\Repository
 */
class FilterRepository {

	/**
	 * Stores the options.
	 *
	 * @var AntispamBeeConfig
	 */
	private $config;

	/**
	 * All filters.
	 *
	 * @var FilterInterface[]
	 */
	private $filters = [];

	/**
	 * FilterRepository constructor.
	 *
	 * @param AntispamBeeConfig $config The configuration.
	 * @param FilterInterface   ...$filters All filters.
	 */
	public function __construct( AntispamBeeConfig $config, FilterInterface ...$filters ) {
		$this->config  = $config;
		$this->filters = $filters;
	}

	/**
	 * Returns a filter for a given ID.
	 *
	 * @param string $id The Id of the filter you want to get returned.
	 *
	 * @throws Runtime When no filter was found.
	 * @return FilterInterface
	 */
	public function from_id( string $id ) : FilterInterface {
		foreach ( $this->registered_filters() as $filter ) {
			if ( $filter->id() === $id ) {
				return $filter;
			}
		}
		throw new Runtime( 'Filter not found.' );
	}


	/**
	 * Returns all active nospam filters.
	 *
	 * @return NoSpamFilterInterface[]
	 */
	public function active_nospam_filters() : array {

		return array_filter(
			$this->active_filters(),
			function ( FilterInterface $check ) {
				return is_a( $check, NoSpamFilterInterface::class );
			}
		);

	}

	/**
	 * Returns all active spam filters.
	 *
	 * @return SpamFilterInterface[]
	 */
	public function active_spam_filters() : array {

		return array_filter(
			$this->active_filters(),
			function ( FilterInterface $check ) {
				return is_a( $check, SpamFilterInterface::class );
			}
		);
	}

	/**
	 * Returns the filters, which are active in the frontend.
	 *
	 * @return FilterInterface[] The list of active filters.
	 */
	public function active_filters() : array {

		$config = $this->config;
		return array_filter(
			$this->filters,
			function ( FilterInterface $filter ) use ( $config ) : bool {
				return $config->has( 'active_filters' ) && isset( $config->get( 'active_filters' )[ $filter->id() ] );
			}
		);

	}

	/**
	 * Returns all registered filters.
	 *
	 * @return FilterInterface[]
	 */
	public function registered_filters() : array {

		return $this->filters;
	}
}
