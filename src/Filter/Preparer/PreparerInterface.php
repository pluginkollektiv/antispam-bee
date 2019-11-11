<?php
/**
 * Some filter need preparation. For example, the TimeSpam Filter needs to render an input field with the
 * current time into the form, so a diff can be made. This is, what preparer can do in order for filter to work.
 *
 * @package Antispam Bee Preparer
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Filter\Preparer;

/**
 * Interface PreparerInterface
 *
 * @package Pluginkollektiv\AntispamBee\Preparer
 */
interface PreparerInterface
{

    /**
     * Registers the Preparer for a specific check.
     *
     * @param mixed $args The arguments.
     *
     * @return bool
     */
    public function register( $args = null) : bool;

    /**
     * Runs the preparation.
     *
     * @return bool
     */
    public function prepare() : bool;
}
