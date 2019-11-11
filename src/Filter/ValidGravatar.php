<?php
/**
 * Using an email address with an associated gravatar is often a good indicator for a data structure
 * not to be spam. This is, what this filter evaluates.
 *
 * @package Antispam Bee Filter
 */
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Class ValidGravatar
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class ValidGravatar implements NoSpamFilterInterface
{

    /**
     * When already created, contains the Options for this filter.
     *
     * @var OptionInterface $options
     */
    private $options;

    /**
     * Creates the options for this filter.
     *
     * @var OptionFactory
     */
    private $option_factory;

    /**
     * The types of data, which can be filtered with this filter.
     *
     * @var array
     */
    private $types;

    /**
     * ValidGravatar constructor.
     *
     * @param OptionFactory $option_factory
     */
    public function __construct( OptionFactory $option_factory, array $types = [CommentDataTypes::COMMENT] )
    {
        $this->option_factory = $option_factory;
        $this->types = $types;
    }

    /**
     * Checks if a gravatar is associated with this data structure.
     *
     * @param DataInterface $data
     *
     * @return float
     */
    public function filter( DataInterface $data ) : float
    {
        $response = wp_safe_remote_get(
            sprintf(
                'https://www.gravatar.com/avatar/%s?d=404',
                md5(strtolower(trim($data->email())))
            )
        );

        if (is_wp_error($response) ) {
            return 0;
        }

        if (wp_remote_retrieve_response_code($response) === 200 ) {
            return 1;
        }

        return 0;
    }

    /**
     * Nothing to register for this filter.
     *
     * @return bool
     */
    public function register() : bool
    {
        return true;
    }

    /**
     * The options for this filter.
     *
     * @return OptionInterface
     */
    public function options() : OptionInterface
    {
        if ($this->options ) {
            return $this->options;
        }
        $args          = [
        'name'        => __('Valid Gravatar', 'antispam-bee'),
        'description' => __('text.', 'antispam-bee'),
        ];
        $this->options = $this->option_factory->from_args($args);
        return $this->options;
    }

    /**
     * Returns the ID for this filter.
     *
     * @return string
     */
    public function id() : string
    {
        return 'gravatar_check';
    }

    /**
     * Returns whether a data object can be cheked.
     *
     * @param DataInterface $data
     *
     * @return bool
     */
    public function can_check_data(DataInterface $data): bool
    {
        return in_array($data->type(), $this->types, true);
    }
}
