<?php # -*- coding: utf-8 -*-
$vendor = dirname(dirname(__DIR__)) . '/vendor/';
if (!file_exists($vendor . 'autoload.php')) {
	die("Please install via Composer before running tests.");
}

require_once $vendor . 'autoload.php';
unset($vendor);
