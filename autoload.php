<?php
/**
 * Load the static classmap and use it to autoload the classes.
 *
 * @package AntispamBee
 */

namespace AntispamBee;

spl_autoload_register(
	static function ( string $fqcn ): void {
		// Static classmap autoloader used in the distributed plugin (no /vendor shipped).
		$classmap = require __DIR__ . '/classmap.php';

		if ( isset( $classmap[ $fqcn ] ) ) {
			require $classmap[ $fqcn ];
		}
	}
);
