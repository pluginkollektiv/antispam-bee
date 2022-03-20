<?php

namespace AntispamBee\Rules;

interface Controllable {
	public static function get_label();
	public static function get_description();
	public static function render();
}