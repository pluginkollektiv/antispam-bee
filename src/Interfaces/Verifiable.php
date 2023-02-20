<?php

namespace AntispamBee\Interfaces;

interface Verifiable {
	public static function verify( $item );

	public static function get_weight();

	public static function get_slug();

	public static function get_supported_types();

	public static function is_final();
}