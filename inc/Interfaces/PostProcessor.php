<?php

namespace AntispamBee\Interfaces;

interface PostProcessor {
	public static function process( $item );

	public static function is_active( $type );

	public static function get_slug();

	public static function get_supported_types();

	public static function marks_as_delete();
}
