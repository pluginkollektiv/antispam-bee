<?php
/**
 * Checks, if a given data is spam or not.
 *
 * @package Antispam Bee
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Handler\SpamHandlerInterface;
use Pluginkollektiv\AntispamBee\Repository\FilterRepository;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class SpamChecker
 *
 * @package Pluginkollektiv\AntispamBee
 */
class SpamChecker {

	/**
	 * The spam handler.
	 *
	 * @var SpamHandlerInterface
	 */
	private $spam_handler;

	/**
	 * The filter repository.
	 *
	 * @var FilterRepository
	 */
	private $repository;

	/**
	 * The reasons repository.
	 *
	 * @var ReasonsRepository
	 */
	private $reasons;

	/**
	 * SpamChecker constructor.
	 *
	 * @param SpamHandlerInterface $spam_handler The spam handler.
	 * @param FilterRepository     $repository The filter repository.
	 * @param ReasonsRepository    $reasons The reasons repository.
	 */
	public function __construct(
		SpamHandlerInterface $spam_handler,
		FilterRepository $repository,
		ReasonsRepository $reasons
	) {
		$this->repository   = $repository;
		$this->spam_handler = $spam_handler;
		$this->reasons      = $reasons;
	}

	/**
	 * Checks the data and if its detected as being spam, the SpamHandler will be executed.
	 *
	 * @param DataInterface $data The data to check.
	 *
	 * @return bool
	 */
	public function check( DataInterface $data ) {

		if ( $this->no_spam_check( $data ) ) {
			return false;
		}

		$is_spam = $this->spam_check( $data );

		if ( $is_spam ) {
			$this->spam_handler->execute( $this->reasons, $data );
		}
		return $is_spam;

	}

	/**
	 * Runs the no spam filter and returns whether the data is nospam or not.
	 *
	 * @param DataInterface $data The data to check.
	 *
	 * @return bool
	 */
	private function no_spam_check( DataInterface $data ) {
		$probability = 0;

		foreach ( $this->repository->active_nospam_filters() as $filter ) {
			if ( $probability >= 1 ) {
				continue;
			}
			if ( ! $filter->can_check_data( $data ) ) {
				continue;
			}

			$propability_for_filter = $filter->filter( $data );
			$probability           += $propability_for_filter;
		}

		return $probability > .5;
	}

	/**
	 * Runs the spam filter and returns whether the data is spam or not.
	 *
	 * @param DataInterface $data The data to check.
	 *
	 * @return bool
	 */
	private function spam_check( DataInterface $data ) : bool {
		$filters = $this->repository->active_spam_filters();
		foreach ( $filters as $filter ) {
			if ( $this->reasons->total_probability() >= 1 ) {
				continue;
			}
			if ( ! $filter->can_check_data( $data ) ) {
				continue;
			}

			$this->reasons->add_reason( $filter->id(), $filter->filter( $data ) );
		}

		return $this->reasons->total_probability() > .5;
	}
}
