<?php

// Register Composer autoloader.
include_once __DIR__ . '/../vendor/autoload.php';

// Registers stub functions.
include_once __DIR__ . '/_stubs/includes.php';

// Registers a PSR-4-compliant SPL autoloader for stubs.
$dir = __DIR__ . '/_stubs/';

spl_autoload_register( function ( $fqn ) use ( $dir ) {
	$file_path = $dir . str_replace( '\\', '/', ltrim( $fqn, '\\' ) ) . '.php';
	if ( is_readable( $file_path ) ) {
		require_once $file_path;

		return true;
	}

	return false;
} );

