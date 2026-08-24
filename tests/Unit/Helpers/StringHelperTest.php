<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\StringHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see StringHelper}.
 */
class StringHelperTest extends TestCase {

	/**
	 * Data provider for pre-release detection.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public static function data_is_pre_release(): array {
		return [
			'stable 3.0.0'            => [ '3.0.0', false ],
			'stable 1.2.3'            => [ '1.2.3', false ],
			'stable with prefix'      => [ 'v2.11.13', false ],
			'rc suffix'               => [ '3.0.0-RC.1', true ],
			'beta suffix'             => [ '3.0.0-beta.2', true ],
			'alpha suffix'            => [ '3.0.0-alpha', true ],
			'mixed case suffix'       => [ '3.0.0-Rc1', true ],
			'build metadata'          => [ '3.0.0-beta.2+build', false ],
			'empty string'            => [ '', false ],
			'not a version'           => [ 'foo', false ],
			'suffix without number'   => [ '-beta', false ],
		];
	}

	/**
	 * Test is_pre_release() against a range of version strings.
	 *
	 * @param string $version Version string.
	 * @param bool   $expected Expected result.
	 *
	 * @dataProvider data_is_pre_release
	 */
	public function test_is_pre_release( string $version, bool $expected ): void {
		self::assertSame( $expected, StringHelper::is_pre_release( $version ) );
	}
}
