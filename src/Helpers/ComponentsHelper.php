<?php

namespace AntispamBee\Helpers;

use AntispamBee\Interfaces\Controllable;
use ReflectionClass;
use const AntispamBee\PLUGIN_PATH;

class ComponentsHelper {

	/**
	 * @param $components Controllable[]
	 * @param $options
	 * $options = array(
	 *   'reaction_type'   => 'comment',
	 *   'only_active'     => true,
	 *   'is_controllable' => false,
	 * );
	 */
	public static function filter( $components, $options ) {
		$reaction_type = isset( $options['reaction_type'] ) ? $options['reaction_type'] : null;
		$only_active   = isset( $options['only_active'] ) ? $options['only_active'] : false;

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

			// Filter by supported types like Comment, Linkback
			$supported_types = $component::get_supported_types();
			if ( ! is_null( $reaction_type ) && ! in_array( $reaction_type, $supported_types ) ) {
				continue;
			}

			// Filters if the component implements the Controllable interface
			$conforms_to_controllable = InterfaceHelper::class_implements_interface( $component, Controllable::class );

			// Filters out components that are not active
			if ( $only_active ) {
				if ( $conforms_to_controllable && ! $component::is_active( $reaction_type ) ) {
					continue;
				}
			}

			$reflection = new ReflectionClass( $component );

			// Remove third-party components with `asb-` prefix.
			if ( 0 !== strpos( $reflection->getFileName(), PLUGIN_PATH ) && 0 === strpos( $component::get_slug(), 'asb-' ) ) {
				error_log( __( 'Antispam Bee: You shall not use `asb-` as slug prefix for your custom rules and post processors.', 'antispam-bee' ) );
				continue;
			}

			$filtered_components[] = $component;
		}

		return $filtered_components;
	}
}
