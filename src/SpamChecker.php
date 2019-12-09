<?php
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Handler\SpamHandlerInterface;
use Pluginkollektiv\AntispamBee\Repository\FilterRepository;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

class SpamChecker
{


    private $spamHandler;
    private $repository;
    private $reasons;

    public function __construct(
        SpamHandlerInterface $spamHandler,
        FilterRepository $repository,
        ReasonsRepository $reasons
    ) {
        $this->repository  = $repository;
        $this->spamHandler = $spamHandler;
        $this->reasons     = $reasons;
    }

    /**
     * Checks the data and if its detected as being spam, the SpamHandler will be executed.
     *
     * @param DataInterface $data The data to check.
     *
     * @return bool
     */
    public function check( DataInterface $data ) : bool
    {

        if ($this->no_spam_check($data) ) {
            return false;
        }

        $is_spam = $this->spam_check($data);

        if ($is_spam ) {
            $this->spamHandler->execute($this->reasons, $data);
        }
        return $is_spam;

    }

    private function no_spam_check( DataInterface $data )
    {
        $probability = 0;

        foreach ( $this->repository->active_nospam_filters() as $filter ) {
            if ($probability >= 1 ) {
                continue;
            }
            if (! $filter->can_check_data($data) ) {
                continue;
            }

            $propability_for_filter = $filter->filter($data);
            $probability           += $propability_for_filter;
        }

        return $probability > .5;
    }

    private function spam_check( DataInterface $data )
    {
        $filters = $this->repository->active_spam_filters();
        foreach ( $filters as $filter ) {
            if ($this->reasons->total_probability() >= 1 ) {
                continue;
            }
            if (! $filter->can_check_data($data) ) {
                continue;
            }

            $this->reasons->add_reason($filter->id(), $filter->filter($data));
        }

        return $this->reasons->total_probability() > .5;
    }
}
