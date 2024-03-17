<?php

use PHPUnit\Framework\TestSuite;

use PHPUnit\Framework\TestListener as PHPUnitTestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Test listener implementation taking care of loading stubs for unit tests.
 *
 * Adapted from https://github.com/inpsyde/WP-REST-Starter by Thorsten Frommen,
 * Inpsyde GmbH.
 *
 * @since 2.7.0
 */
class TestListener implements PHPUnitTestListener {
	use TestListenerDefaultImplementation;

	/**
	 * Performs individual test-suite-specific actions.
	 *
	 * This gets triggered by PHPUnit when a new test suite gets run.
	 *
	 * @since 2.7.0
	 *
	 * @param TestSuite $suite Test suite object.
	 */
	public function startTestSuite( TestSuite $suite ): void {
		switch ( strtolower( $suite->getName() ) ) {
			case 'unit':
				$this->stub_functions();
				$this->autoload_stubs();
				break;

			case 'integration':
				$this->stub_functions();
				$this->autoload_stubs();
				break;
		}
	}

	/**
	 * Registers stub functions.
	 *
	 * @since 2.7.0
	 */
	private function stub_functions() {
		include_once __DIR__ . '/_stubs/includes.php';
	}

	/**
	 * Registers a PSR-4-compliant SPL autoloader for stubs.
	 * The global namespace is mapped to the stubs dir.
	 *
	 * @since 2.7.0
	 */
	private function autoload_stubs() {
		$dir = __DIR__ . '/_stubs/';

		spl_autoload_register( function ( $fqn ) use ( $dir ) {

			$file_path = $dir . str_replace( '\\', '/', ltrim( $fqn, '\\' ) ) . '.php';
			if ( is_readable( $file_path ) ) {
				require_once $file_path;

				return true;
			}

			return false;
		} );
	}
}
