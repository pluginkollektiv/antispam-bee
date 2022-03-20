<?php

namespace AntispamBee\Rules;

interface Verifiable {
	public static function init();

	public static function verify( $data );

	public static function get_name();

	public static function get_weight();

	public static function get_slug();

	public static function is_final();
}