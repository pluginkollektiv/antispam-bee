<?php

namespace AntispamBee\Helpers;

class InterfaceHelper {
	/**
	 * Checks if a class implements an interface.
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @param string $interface  Fully-qualified interface name.
	 *
	 * @return bool
	 */
	public static function class_implements_interface( $class_name, $interface ) {
		return self::class_implements_interfaces( $class_name, [ $interface ] );
	}

	/**
	 * Check if a class implements one or more interfaces.
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @param array  $interfaces Array of fully-qualified interface names.
	 *
	 * @return bool
	 */
	public static function class_implements_interfaces( $class_name, $interfaces ) {
		if ( ! is_string( $class_name ) || ! class_exists( $class_name ) ) {
			return false;
		}

		if ( empty( $interfaces ) || ! is_array( $interfaces ) ) {
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
