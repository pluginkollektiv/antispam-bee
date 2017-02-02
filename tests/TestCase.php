<?php

namespace AntispamBee\Tests;

use Brain\Monkey;
use Mockery;
use PHPUnit_Framework_TestCase;

/**
 * Abstract base class for all test case implementations.
 *
 * Adapted from https://github.com/inpsyde/WP-REST-Starter by Thorsten Frommen,
 * Inpsyde GmbH.
 *
 * @since   2.7.0
 */
abstract class TestCase extends PHPUnit_Framework_TestCase {

	/**
	 * Prepares the test environment before each test.
	 *
	 * @since 2.7.0
	 *
	 * @return void
	 */
	protected function setUp() {

		parent::setUp();
		Monkey::setUpWP();
	}

	/**
	 * Cleans up the test environment after each test.
	 *
	 * @since 2.7.0
	 *
	 * @return void
	 */
	protected function tearDown() {

		Monkey::tearDownWP();
		Mockery::close();
		parent::tearDown();
	}
}
