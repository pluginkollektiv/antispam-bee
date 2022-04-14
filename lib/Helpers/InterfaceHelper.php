<?php

namespace AntispamBee\Helpers;

class InterfaceHelper {

	// Todo: adapt class methods because the callable methods now have to be stored under the interface key
	/**
	 * Tries to call a method with different approaches. Firstly it tries to call it via object/fqn and method name.
	 * Next it tries to call the array element directly. At least it returns the value of the node.
	 *
	 * @param array  $array
	 * @param string $interface
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed|null
	 */
	public static function call( $array, $interface, $method, $args = [] ) {
		// Just return if there is no interface key in the array
		if ( ! isset ( $array[ $interface ] ) ) {
			return null;
		}

		// Call the method if it is an object or fully qualified name and the method is callable
		$callable = [ $array[ $interface ], $method ];
		if ( is_callable( $callable ) ) {
			return call_user_func( $callable, $args );
		}

		// Call the method if the callable is stored in the array
		$callable = isset( $array[ $interface ][ $method ] ) ? $array[ $interface ][ $method ] : null;
		if ( is_callable( $callable ) ) {
			return call_user_func( $callable, $args );
		}

		// Return the value at least, if nothing other works
		return $callable;
	}

	public static function class_implements_interface( $class_name, $interface ) {
		if ( ! is_string( $class_name ) || ! class_exists( $class_name ) ) {
			return false;
		}

		$interfaces = class_implements( $class_name );
		if ( false === $interfaces || empty( $interfaces ) ) {
			return false;
		}

		if ( ! in_array( $interface, $interfaces, true ) ) {
			return false;
		}

		return true;
	}

	public static function object_implements_interface( $object, $interface ) {
		if ( ! is_object( $object ) ) {
			return false;
		}

		$ref_class = new \ReflectionClass( $object );

		$interfaces = $ref_class->getInterfaces();
		if ( false === $interfaces || empty( $interfaces ) ) {
			return false;
		}

		if ( ! array_key_exists( $interface, $interfaces ) ) {
			return false;
		}

		return true;
	}

	public static function array_conforms_to_interface( $array, $interface ) {
		if ( ! is_array( $array ) ) {
			return false;
		}

		$refClass = new \ReflectionClass( $interface );

		$callables = [];
		foreach ( $refClass->getMethods() as $method ) {
			$callables[] = $method->name;
		}

		return self::array_has_callables( $array, $callables );
	}

	public static function conforms_to_interface( $array_or_object, $interface ) {
		return self::array_conforms_to_interface( $array_or_object, $interface ) ||
		       self::class_implements_interface( $array_or_object, $interface ) ||
		       self::object_implements_interface( $array_or_object, $interface );
	}

	public static function array_has_callables( $array, $callable_names ) {
		foreach ( $callable_names as $callable_name ) {
			if ( ! ( isset( $array[ $callable_name ] ) && is_callable( $array[ $callable_name ] ) ) ) {
				return false;
			}
		}

		return true;
	}

	public static function object_to_callable_array( $object ) {
		$ref_class = new \ReflectionClass( $object );

		$callable_array = [];
		foreach ( $ref_class->getMethods() as $method ) {
			$callable_array[ $method->name ] = [ $object, $method->name ];
		}

		return $callable_array;
	}
}
