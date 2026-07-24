<?php
/**
 * PHPUnit bootstrap: load the Composer autoloader and the WP function stubs.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Register global WP function stubs (functions cannot be autoloaded).
require_once __DIR__ . '/_stubs/includes.php';
