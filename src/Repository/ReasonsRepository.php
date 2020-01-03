<?php
/**
 * The Reasons repository.
 *
 * Why a data structure is identified as spam can have several reasons. This repository
 * contains those reasons with the probability the reason has added to being spam.
 *
 * @package Antispam Bee Filter
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Repository;

/**
 * Class CheckRepository
 *
 * @package Pluginkollektiv\AntispamBee\Repository
 */
class ReasonsRepository {

	/**
	 * All reasons.
	 *
	 * @var array
	 */
	private $reasons = [];

	/**
	 * Adds a reason.
	 *
	 * @param string $reason      The reason.
	 * @param float  $probability The calculated probability by the reason.
	 *
	 * @return bool
	 */
	public function add_reason( string $reason, float $probability ) : bool {
		if ( isset( $this->reasons[ $reason ] ) ) {
			return false;
		}
		$this->reasons[ $reason ] = $probability;
		return true;
	}

	/**
	 * Returns all reasons.
	 *
	 * @return array
	 */
	public function get_reasons() : array {
		return array_keys( $this->reasons );
	}

	/**
	 * Returns the probability of a reason.
	 *
	 * @param string $reason The reason for which the probability should be returned.
	 *
	 * @return float
	 */
	public function probability_by_reason( string $reason ) : float {
		return ( ! isset( $this->reasons[ $reason ] ) ) ? 0 : $this->reasons[ $reason ];
	}

	/**
	 * Returns all reasons with the probabilites. The array keys are the reasons, the array values the probabilities.
	 *
	 * @return array
	 */
	public function all() : array {
		return $this->reasons;
	}

	/**
	 * The calculated overall probability.
	 *
	 * @return float
	 */
	public function total_probability() : float {
		$probability = 0;
		foreach ( $this->get_reasons() as $reason ) {
			$probability += $this->probability_by_reason( $reason );
		}
		return $probability;
	}
}
