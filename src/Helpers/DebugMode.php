<?php

namespace AntispamBee\Helpers;

class DebugMode {
    protected static $debug_mode_enabled = null;

    public static function enabled() {
        if ( static::$debug_mode_enabled === null ) {
            static::$debug_mode_enabled = defined( 'ANTISPAM_BEE_DEBUG_MODE_ENABLED' ) ? \ANTISPAM_BEE_DEBUG_MODE_ENABLED : false;
        }

        return static::$debug_mode_enabled;
    }

    public static function log( string $message ) {
        if ( ! static::enabled() ) {
            return;
        }

        $date = date( 'Y-m-d' );
        $time = date( 'H-i-s' );
        $log_dir = defined( 'ANTISPAM_BEE_DEBUG_MODE_LOG_DIR' ) ? \ANTISPAM_BEE_DEBUG_MODE_LOG_DIR : \WP_CONTENT_DIR;
        if ( ! is_dir( $log_dir ) ) {
            error_log( "The directory set for Antispam Bee debug logging does not exist: {$log_dir}" );
        }
        error_log( "[{$date} {$time}] {$message}\n", 3, "{$log_dir}/asb-debug.{$date}.log" );
    }
}