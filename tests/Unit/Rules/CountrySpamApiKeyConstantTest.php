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
	public function test_constant_takes_precedence_over_the_filter() {
		define( 'ANTISPAM_BEE_IPLOCATE_API_KEY', 'from-constant' );

		expectApplied( 'antispam_bee_country_spam_apikey' )->never();

		self::assertSame( 'from-constant', self::get_api_key(), 'The constant should win over the filter' );
	}

	/**
	 * An empty constant is treated as unset, so a filtered key still applies.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_empty_constant_falls_through_to_the_filter() {
		define( 'ANTISPAM_BEE_IPLOCATE_API_KEY', '' );

		expectApplied( 'antispam_bee_country_spam_apikey' )
			->once()
			->andReturn( 'from-filter' );

		self::assertSame( 'from-filter', self::get_api_key(), 'An empty constant should fall through to the filter' );
	}
}
