<?php
/**
 * Registers all modules for Antispam Bee.
 *
 * @package Antispam Bee
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee;

use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;

/**
 * Class Registrar
 *
 * @package Pluginkollektiv\AntispamBee
 */
class Registrar
{

    /**
     * The action hook, which the registrar fires, so 3rd parties can hook into.
     */
    const ACTION_ANTISPAMBEE_REGISTER = 'antispam_bee_register';

    /**
     * The registered filters.
     *
     * @var FilterInterface[]
     */
    private $registered_filters = [];

    /**
     * The registered Post Processors.
     *
     * @var PostProcessorInterface[]
     */
    private $post_processors = [];

    /**
     * Since the action returns the Registrar itself, you could run into an
     * infinite loop, if you would use `run()` inside of the loop again. This
     * boolean prevents this.
     *
     * @var bool
     */
    private $is_running = false;

    /**
     * Runs the registration process by basically firing the action.
     *
     * @return bool
     */
    public function run() : bool
    {
        if ($this->is_running ) {
            return false;
        }
        $this->is_running = true;
        do_action(self::ACTION_ANTISPAMBEE_REGISTER, $this);
        $this->is_running = false;
        return ! $this->is_running && (bool) did_action(self::ACTION_ANTISPAMBEE_REGISTER);
    }

    /**
     * When you are hooked into the action, you can register your filter using this method.
     *
     * @param Filter\FilterInterface ...$filter_list The filters you want to register.
     *
     * @return bool
     */
    public function register_filter( FilterInterface ...$filter_list ) : bool
    {

        $success = true;
        foreach ( $filter_list as $filter ) {
            if (isset($this->registered_filters[ $filter->id() ]) ) {
                $success = false;
                continue;
            }
            $this->registered_filters[ $filter->id() ] = $filter;
        }
        return $success;
    }

    /**
     * Inside of the hook you can use this method to register your post processors.
     *
     * @param PostProcessor\PostProcessorInterface ...$post_processor_list The list of post processors you want to register.
     *
     * @return bool
     */
    public function register_post_processor( PostProcessorInterface ...$post_processor_list ) : bool
    {

        $success = true;
        foreach ( $post_processor_list as $processor ) {
            if (isset($this->post_processors[ $processor->id() ]) ) {
                $success = false;
                continue;
            }
            $this->post_processors[ $processor->id() ] = $processor;
        }
        return $success;
    }

    /**
     * The registered filters.
     *
     * @return FilterInterface[]
     */
    public function registered_filters() : array
    {
        return array_values($this->registered_filters);
    }

    /**
     * The registered post processors.
     *
     * @return PostProcessorInterface[]
     */
    public function registered_post_processors() : array
    {
        return array_values($this->post_processors);
    }
}
