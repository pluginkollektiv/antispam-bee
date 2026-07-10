<?php
/**
 * PHPStan bootstrap file.
 *
 * Defines constants that are set dynamically via define( __NAMESPACE__ . '\...', ... )
 * in antispam_bee.php so PHPStan can resolve them during static analysis.
 *
 * @package AntispamBee
 */

namespace AntispamBee;

define( __NAMESPACE__ . '\MAIN_PLUGIN_FILE', __DIR__ . '/antispam_bee.php' );
define( __NAMESPACE__ . '\PLUGIN_PATH', __DIR__ . '/' );
define( __NAMESPACE__ . '\PLUGIN_VERSION', '3.0.0-alpha.15' );
// These constants are user-defined (typically in wp-config.php), so their values
// are dynamic. They are listed under `dynamicConstantNames` in phpstan.neon, so
// PHPStan uses the declared type instead of narrowing to these literal values.
define( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED', false );
define( 'ANTISPAM_BEE_LOG_FILE', 'asb.log' );
