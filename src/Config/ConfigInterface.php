<?php
/**
 * The configuration interface
 *
 * @package Antispam Bee Config
 */

namespace Pluginkollektiv\AntispamBee\Config;

/**
 * Interface ConfigInterface
 *
 * @package Pluginkollektiv\AntispamBee\Config
 */
interface ConfigInterface {

	/**
	 * Whether a given key exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key) : bool;

	/**
	 * Returns the value for a given key.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	public function get( string $key);

	/**
	 * Whether a specific sub config exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has_config( string $key ) : bool;

	/**
	 * Returns a specific sub config.
	 *
	 * @param string $key The key.
	 *
	 * @return ConfigInterface
	 */
	public function get_config( string $key ) : ConfigInterface;

	/**
	 * Sets a specific configuration.
	 *
	 * @param string $key The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return bool
	 */
	public function set( string $key, $value) : bool;

	/**
	 * Persists the current configuration.
	 *
	 * @return bool
	 */
	public function persist() : bool;
}
