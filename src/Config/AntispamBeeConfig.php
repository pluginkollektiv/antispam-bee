<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Config;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Filter\FilterFactory;
use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\Filter\NullFilter;
use Pluginkollektiv\AntispamBee\PostProcessor\NullPostProcessor;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorFactory;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;

/**
 * Class Options
 *
 * @package Pluginkollektiv\AntispamBee
 */
class AntispamBeeConfig implements ConfigInterface
{

    private $config;
    private $config_key;

    /**
     * @var ConfigInterface[] $sub_configs
     */
    private $sub_configs;

    public function __construct(
        array $config,
        string $config_key,
        array $sub_configs
    ) {

        $this->config                 = $config;
        $this->config_key             = $config_key;
        foreach ( $sub_configs as $key => $val ) {
            if (! is_a($val, ConfigInterface::class) ) {
                continue;
            }
            $this->sub_configs[ $key ] = $val;
        }
    }

    /**
     * Returns all the Checks, which Antispam Bee Core delivers.
     *
     * @return string[]
     */
    public function antispambee_filters() : array
    {
        return [
            'honeypot',
            'bbcode_check',
            'spam_ip',
            'country_code',
            'time_check',
            'gravatar_check',
        ];
    }

    public function antispambee_postprocessor() : array
    {
        return [
        'rest_in_peace',
        'spamlog',
        'savereason',
        ];
    }

    public function has( string $key ) : bool
    {
        return ( isset($this->config[ $key ]) );
    }

    public function get( string $key )
    {
        $value = $this->config[ $key ];
        return $value;
    }

    public function set( string $key, $value ) : bool
    {
        $this->config[ $key ] = $value;
        return $this->has($key) && $this->config[ $key ] === $value;
    }

    public function has_config( string $key ) : bool
    {
        return isset($this->sub_configs[ $key ]);
    }

    public function get_config( string $key ) : ConfigInterface
    {
        if (! $this->has_config($key) ) {
            throw new Runtime('Config not found.');
        }
        return $this->sub_configs[ $key ];
    }

    public function activate_filter( FilterInterface $filter ) : bool
    {
        if (! $filter->options()->activateable() ) {
            return false;
        }
        $this->config['active_filters'][ $filter->id() ] = true;
        return true;
    }

    public function deactivate_filter( FilterInterface $filter ) : bool
    {
        if (! $filter->options()->activateable() ) {
            return false;
        }
        unset($this->config['active_filters'][ $filter->id() ]);
        return true;
    }

    public function activate_processor( PostProcessorInterface $processor ) : bool
    {
        if (! $processor->options()->activateable() ) {
            return false;
        }
        $this->config['active_processors'][ $processor->id() ] = true;
        return true;
    }

    public function deactivate_processor( PostProcessorInterface $processor ) : bool
    {
        if (! $processor->options()->activateable() ) {
            return false;
        }
        unset($this->config['active_processors'][ $processor->id() ]);
        return true;
    }

    public function persist() : bool
    {
        $success = true;
        foreach ( $this->sub_configs as $config ) {
            if (! $config->persist() ) {
                $success = false;
            }
        }
        return update_option($this->config_key, $this->config) && $success;
    }
}
