<?php

namespace AntispamBee\Helpers;

use AntispamBee\Interfaces\Controllable;
use ReflectionClass;

class Components {

	/**
	 * @param $components Controllable[]
	 * @param $options
	 * $options = array(
	 *   'type'            => 'comment',
	 *   'only_active'       => true,
	 *   'is_controllable' => false,
	 * );
	 */
	public static function filter( $components, $options ) {
		// Todo: Find way to ensure that rule slugs are unique (adding a unique prefix per extension that adds filters/rules, or something like that).
		// Todo: Check if other things can break it too

		$type = isset( $options['type'] ) ? $options['type'] : null;
		$only_active = isset( $options['only_active'] ) ? $options['only_active'] : false;

		$filtered_components = [];
		foreach ( $components as $component ) {
			if ( isset( $options['implements'] ) ) {
				$implements = $options['implements'];

				if ( is_string( $implements ) ) {
					$implements_interfaces = InterfaceHelper::class_implements_interface( $component, $implements );
				}

				if ( is_array( $implements ) ) {
					$implements_interfaces = InterfaceHelper::class_implements_interfaces( $component, $implements );
				}

				if ( ! $implements_interfaces ) {
					continue;
				}
			}

			$supported_types = $component::get_supported_types();
			if ( ! is_null( $type ) && ! in_array( $type, $supported_types ) ) {
				continue;
			}

			$conforms_to_controllable = InterfaceHelper::class_implements_interface( $component, Controllable::class );

			if ( $only_active ) {
				if ( $conforms_to_controllable && ! $component::is_active( $type ) ) {
					continue;
				}
			}

			$reflection = new ReflectionClass( $component );

			// Remove third-party components with `asb-` prefix.
			if ( 0 !== strpos( $reflection->getFileName(), ANTISPAM_BEE_PATH ) && 0 === strpos( $component::get_slug(), 'asb-' ) ) {
				error_log( __( 'Antispam Bee: You shall not use `asb-` as slug prefix for your custom rules and post processors.', 'antispam-bee' ) );
				continue;
			}

			$filtered_components[] = $component;
		}

		return $filtered_components;
	}
}
