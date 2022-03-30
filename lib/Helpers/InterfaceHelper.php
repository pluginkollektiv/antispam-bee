<?php

namespace AntispamBee\Helpers;

class InterfaceHelper {

	// Todo: adapt method because the methods have to be stored under the verifiable key
	public static function call( $array, $interface, $method, ...$args ) {
		if ( isset( $array[ $method ] ) && is_callable( $array[ $method ] ) ) {
			return call_user_func( $array[ $method ], $args );
		}

		if ( isset( $array[ $interface ] ) && is_callable( array( $array[ $interface ], $method ) ) ) {
			return call_user_func( [ $array[ $interface ], $method ], $args );
		}
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
