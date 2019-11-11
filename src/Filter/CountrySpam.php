<?php
/**
 * The Countrytabase Spam Filter.
 *
 * You can black- and whitelist countries. If the data structure comes from a blacklisted country, it
 * will be marked as spam. If you have whitelisted countries and the data structure does not come from
 * one of those countries, it will be marked as spam.
 *
 * For this reason, we send an anonymized part of the IP to https://api.ip2country.info which returns
 * the country code.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Helper\IP;

/**
 * Class CountrySpam
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class CountrySpam implements SpamFilterInterface
{


    /**
     * Used to anonymize the IP.
     *
     * @var IP $ip The IP helper.
     */
    private $ip;

    /**
     * When already created, this contains the Options for this filter.
     *
     * @var OptionInterface $options
     */
    private $options;

    /**
     * Builds the options for this filter.
     *
     * @var OptionFactory $option_factory
     */
    private $option_factory;

    /**
     * The types of data, which can be filtered with this filter.
     *
     * @var array
     */
    private $types;

    /**
     * CountrySpam constructor.
     *
     * @param OptionFactory $option_factory
     * @param IP            $ip
     * @param array         $types
     */
    public function __construct(
        OptionFactory $option_factory,
        IP $ip,
        array $types = CommentDataTypes::ALL
    ) {
        $this->ip             = $ip;
        $this->option_factory = $option_factory;
        $this->types = $types;
    }

    /**
     * Returns the list of whitelisted countries.
     *
     * @return array
     */
    private function whitelist() : array
    {
        $list = ( $this->options()->has('country_white') ) ? (string) $this->options()->get('country_white') : '';
        return $this->turn_list_into_array($list);
    }

    /**
     * Returns the list of blacklisted countries.
     *
     * @return array
     */
    private function blacklist() : array
    {
        $list = ( $this->options()->has('country_black') ) ? (string) $this->options()->get('country_black') : '';
        return $this->turn_list_into_array($list);
    }

    /**
     * Turns a csv string into an array.
     *
     * @param string $list
     *
     * @return array
     */
    private function turn_list_into_array( string $list )
    {
        return preg_split(
            '/[\s,;]+/',
            $list,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
    }

    /**
     * Checks, if the data came from a white- or blacklisted country.
     *
     * @param DataInterface $data
     *
     * @return float
     */
    public function filter( DataInterface $data ) : float
    {
        $white = $this->whitelist();
        $black = $this->blacklist();

        if (empty($white) && empty($black) ) {
            return (float) 0;
        }

        $response = wp_safe_remote_head(
            esc_url_raw(
                sprintf(
                    'https://api.ip2country.info/ip?%s',
                    $this->ip->anonymize_ip($data->ip())
                ),
                'https'
            )
        );

        if (is_wp_error($response) ) {
            return (float) 0;
        }

        if (wp_remote_retrieve_response_code($response) !== 200 ) {
            return (float) 0;
        }

        $country = (string) wp_remote_retrieve_header($response, 'x-country-code');

        if (empty($country) || strlen($country) !== 2 ) {
            return (float) 0;
        }

        if (! empty($black) ) {
            return (float) ( in_array($country, $black, true) );
        }

        return (float) ( ! in_array($country, $white, true) );
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
     * Returns the options for this filter.
     *
     * @return OptionInterface
     */
    public function options() : OptionInterface
    {
        if ($this->options ) {
            return $this->options;
        }
        $args          = [
        'name'        => __('Country Spam', 'antispam-bee'),
        'description' => __('text.', 'antispam-bee'),
        'fields'      => [
        'country_black' => [
                    'type'  => 'text',
                    'label' => __('Country Blacklist', 'antispam-bee'),
        ],
        'country_white' => [
        'type'  => 'text',
        'label' => __('Country Whitelist', 'antispam-bee'),
        ],
        ],
        ];
        $this->options = $this->option_factory->from_args($args);
        return $this->options;
    }

    /**
     * Returns the ID of this filter.
     *
     * @return string
     */
    public function id() : string
    {
        return 'country_code';
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
