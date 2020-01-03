<?php
/**
 * A generic WP option configuration.
 *
 * @package Antispam Bee Config
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Config;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;

/**
 * Class GenericWPOption
 *
 * @package Pluginkollektiv\AntispamBee\Config
 */
class GenericWPOption implements ConfigInterface {

	/**
	 * The option key.
	 *
	 * @var string
	 */
	private $option_key;

	/**
	 * The options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * GenericWPOption constructor.
	 *
	 * @param string $option_key The option key.
	 */
	public function __construct( string $option_key ) {
		$this->option_key = $option_key;
		$this->options    = (array) get_option( $option_key, [] );
	}

	/**
	 * Whether a given key exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has( string $key ) : bool {
		return isset( $this->options[ $key ] );
	}

	/**
	 * Returns the value for a given key.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	public function get( string $key ) {
		return $this->options[ $key ];
	}

	/**
	 * Whether a specific sub config exists.
	 *
	 * @param string $key The key.
	 *
	 * @return bool
	 */
	public function has_config( string $key ) : bool {
		return false;
	}

	/**
	 * Returns a specific sub config.
	 *
	 * @param string $key The key.
	 * @throws Runtime Always as no sub configs exist for this implementation.
	 *
	 * @return void
	 */
	public function get_config( string $key ) : ConfigInterface {
		throw new Runtime( 'Config not found.' );
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
		$this->options[ $key ] = $value;
		return $this->has( $key );
	}

	/**
	 * Persists the current configuration.
	 *
	 * @return bool
	 */
	public function persist() : bool {
		return update_option( $this->option_key, $this->options );
	}
}
