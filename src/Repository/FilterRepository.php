<?php
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
	 * @var FilterInterface[]
	 */
	private $filters = [];

	/**
	 * CheckRepository constructor.
	 *
	 * @param AntispamBeeConfig $config
	 * @param FilterInterface[] ...$filters
	 */
	public function __construct( AntispamBeeConfig $config, FilterInterface ...$filters ) {
		$this->config  = $config;
		$this->filters = $filters;
	}

	public function from_id( string $id ) : FilterInterface {
		foreach ( $this->registered_filters() as $filter ) {
			if ( $filter->id() === $id ) {
				return $filter;
			}
		}
		throw new Runtime( 'Filter not found.' );
	}


	/**
	 * @return NoSpamFilterInterface[]
	 */
	public function active_nospam_filters() : array {

		return array_filter(
			$this->active_filters(),
			function( FilterInterface $check ) {
				return is_a( $check, NoSpamFilterInterface::class );
			}
		);

	}

	/**
	 * @return SpamFilterInterface[]
	 */
	public function active_spam_filters() : array {

		return array_filter(
			$this->active_filters(),
			function( FilterInterface $check ) {
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
			function( FilterInterface $filter ) use ( $config ) : bool {
				return $config->has( 'active_filters' ) && isset( $config->get( 'active_filters' )[ $filter->id() ] );
			}
		);

	}

	/**
	 * @return FilterInterface[]
	 */
	public function registered_filters() : array {

		return $this->filters;
	}
}
