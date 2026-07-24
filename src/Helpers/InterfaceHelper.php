<?php
/**
 * Interface helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Interface helper.
 *
 * @package AntispamBee\Helpers
 */
class InterfaceHelper {
	/**
	 * Check if a class implements an interface.
	 *
	 * @param string $class_name     Fully-qualified class name.
	 * @param string $interface_name Fully-qualified interface name.
	 *
	 * @return bool Whether the class implements the interface.
	 */
	public static function class_implements_interface( string $class_name, string $interface_name ): bool {
		return self::class_implements_interfaces( $class_name, [ $interface_name ] );
	}

	/**
	 * Check if a class implements one or more interfaces.
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @param array  $interfaces An array of fully-qualified interface names.
	 *
	 * @return bool Whether the class implements the interfaces.
	 */
	public static function class_implements_interfaces( string $class_name, array $interfaces ): bool {
		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		if ( empty( $interfaces ) ) {
			return false;
		}

		$implements = class_implements( $class_name );
		if ( empty( $implements ) ) {
			return false;
		}

		foreach ( $interfaces as $interface ) {
			if ( ! is_string( $interface ) ) {
				return false;
			}

			if ( ! in_array( $interface, $implements, true ) ) {
				return false;
			}
		}

		return true;
	}
}
