<?php
/**
 * The Antispam Bee configuration.
 *
 * @package Antispam Bee Config
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Config;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;

/**
 * Class Options
 *
 * @package Pluginkollektiv\AntispamBee
 */
class AntispamBeeConfig implements ConfigInterface {

	/**
	 * The config.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * The configuration key.

	 * @var string
	 */
	private $config_key;

	/**
	 * The sub configurations.
	 *
	 * @var ConfigInterface[] $sub_configs
	 */
	private $sub_configs;

	/**
	 * AntispamBeeConfig constructor.
	 *
	 * @param array  $config The current configuration.
	 * @param string $config_key The configuration key.
	 * @param array  $sub_configs The sub configuration objects.
	 */
	public function __construct(
		array $config,
		string $config_key,
		array $sub_configs
	) {

		$this->config     = $config;
		$this->config_key = $config_key;
		foreach ( $sub_configs as $key => $val ) {
			if ( ! is_a( $val, ConfigInterface::class ) ) {
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
	public function antispambee_filters() : array {
		return [
			'honeypot',
			'bbcode_check',
			'spam_ip',
			'country_code',
			'time_check',
			'gravatar_check',
		];
	}

	/**
	 * The core Post Processors IDs.
	 *
	 * @return string[]
	 */
	public function antispambee_postprocessor() : array {
		return [
			'rest_in_peace',
			'spamlog',
			'savereason',
		];
	}

	/**
	 * Whether a given key exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key ) : bool {
		return ( isset( $this->config[ $key ] ) );
	}

	/**
	 * Returns the value for a given key.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	public function get( string $key ) {
		$value = $this->config[ $key ];
		return $value;
	}

	/**
	 * Sets a specific configuration.
	 *
	 * @param string $key The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return bool
	 */
	public function set( string $key, $value ) : bool {
		$this->config[ $key ] = $value;
		return $this->has( $key ) && $this->config[ $key ] === $value;
	}

	/**
	 * Whether a specific sub config exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has_config( string $key ) : bool {
		return isset( $this->sub_configs[ $key ] );
	}

	/**
	 * Returns a specific sub config.
	 *
	 * @param string $key The key.
	 * @throws Runtime When sub config was not found.
	 *
	 * @return ConfigInterface
	 */
	public function get_config( string $key ) : ConfigInterface {
		if ( ! $this->has_config( $key ) ) {
			throw new Runtime( 'Config not found.' );
		}
		return $this->sub_configs[ $key ];
	}

	/**
	 * Activate a specific filter.
	 *
	 * @param FilterInterface $filter The filter to activate.
	 *
	 * @return bool
	 */
	public function activate_filter( FilterInterface $filter ) : bool {
		if ( ! $filter->options()->activateable() ) {
			return false;
		}
		$this->config['active_filters'][ $filter->id() ] = true;
		return true;
	}

	/**
	 * Deactivate a specific filter.
	 *
	 * @param FilterInterface $filter The filter to deactivate.
	 *
	 * @return bool
	 */
	public function deactivate_filter( FilterInterface $filter ) : bool {
		if ( ! $filter->options()->activateable() ) {
			return false;
		}
		unset( $this->config['active_filters'][ $filter->id() ] );
		return true;
	}

	/**
	 * Activate a specific Post Processor.
	 *
	 * @param PostProcessorInterface $processor The processor.
	 *
	 * @return bool
	 */
	public function activate_processor( PostProcessorInterface $processor ) : bool {
		if ( ! $processor->options()->activateable() ) {
			return false;
		}
		$this->config['active_processors'][ $processor->id() ] = true;
		return true;
	}

	/**
	 * Deactivate a specific Post Processor.
	 *
	 * @param PostProcessorInterface $processor The post processor.
	 *
	 * @return bool
	 */
	public function deactivate_processor( PostProcessorInterface $processor ) : bool {
		if ( ! $processor->options()->activateable() ) {
			return false;
		}
		unset( $this->config['active_processors'][ $processor->id() ] );
		return true;
	}

	/**
	 * Persists the current configuration.
	 *
	 * @return bool
	 */
	public function persist() : bool {
		$success = true;
		foreach ( $this->sub_configs as $config ) {
			if ( ! $config->persist() ) {
				$success = false;
			}
		}
		return update_option( $this->config_key, $this->config ) && $success;
	}
}
