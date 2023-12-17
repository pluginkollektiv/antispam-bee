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
        $content_dir = \WP_CONTENT_DIR;
        error_log( "[{$date} {$time}] {$message}\n", 3, "{$content_dir}/asb-debug.{$date}.log" );
    }
}