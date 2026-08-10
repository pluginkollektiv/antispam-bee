<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\CountrySpam;
use ReflectionMethod;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;

/**
 * Covers the `ANTISPAM_BEE_IPLOCATE_API_KEY` constant.
 *
 * A constant cannot be undefined once it is set, so each case needs its own
 * process. That rules out `AbstractRuleTestCase`, whose constructor signature
 * PHPUnit cannot reproduce in a separate process.
 */
class CountrySpamApiKeyConstantTest extends TestCase {

	/**
	 * Call the private `get_api_key` method.
	 *
	 * @return string The resolved API key.
	 */
	private static function get_api_key(): string {
		$method = new ReflectionMethod( CountrySpam::class, 'get_api_key' );
		$method->setAccessible( true );

		return $method->invoke( null );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_constant_is_used_as_the_key() {
		define( 'ANTISPAM_BEE_IPLOCATE_API_KEY', 'from-constant' );

		expectApplied( 'antispam_bee_iplocate_api_key' )
			->once()
			->with( 'from-constant' )
			->andReturnFirstArg();

		self::assertSame( 'from-constant', self::get_api_key(), 'The constant should provide the key' );
	}

	/**
	 * The constant is only the default handed to the filter, so a filter still wins.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_filter_overrides_the_constant() {
		define( 'ANTISPAM_BEE_IPLOCATE_API_KEY', 'from-constant' );

		expectApplied( 'antispam_bee_iplocate_api_key' )
			->once()
			->with( 'from-constant' )
			->andReturn( 'from-filter' );

		self::assertSame( 'from-filter', self::get_api_key(), 'The filter should win over the constant' );
	}
}
