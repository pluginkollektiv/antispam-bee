<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\LookupCache;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see LookupCache}.
 */
class LookupCacheTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		$GLOBALS['asb_test_transients'] = [];
		LookupCache::flush_memo();
		when( 'wp_salt' )->justReturn( 'a-fixed-test-salt' );
	}

	public function test_a_repeated_lookup_is_answered_from_the_cache(): void {
		$calls = 0;
		$lookup = function () use ( &$calls ) {
			++$calls;

			return 'DE';
		};

		self::assertSame( 'DE', LookupCache::remember( 'country', '203.0.113.0', $lookup ) );

		// A second request: the memo is gone, but the transient is not.
		LookupCache::flush_memo();
		self::assertSame( 'DE', LookupCache::remember( 'country', '203.0.113.0', $lookup ) );

		self::assertSame( 1, $calls, 'the lookup should have been performed once' );
	}

	public function test_a_different_subject_is_looked_up_separately(): void {
		$calls = 0;
		$lookup = function () use ( &$calls ) {
			++$calls;

			return 'DE';
		};

		LookupCache::remember( 'country', '203.0.113.0', $lookup );
		LookupCache::remember( 'country', '198.51.100.0', $lookup );

		self::assertSame( 2, $calls, 'each subject needs its own lookup' );
	}

	/**
	 * Without this a failing endpoint is contacted again for every reaction,
	 * which is the behaviour the cache exists to prevent.
	 */
	public function test_a_failed_lookup_is_cached_too(): void {
		$calls = 0;
		$lookup = function () use ( &$calls ) {
			++$calls;

			return null;
		};

		self::assertNull( LookupCache::remember( 'country', '203.0.113.0', $lookup ) );

		LookupCache::flush_memo();
		self::assertNull( LookupCache::remember( 'country', '203.0.113.0', $lookup ) );

		self::assertSame( 1, $calls, 'a failure should not be retried for every reaction' );
	}

	public function test_the_memo_answers_a_repeat_within_one_request(): void {
		$calls = 0;
		$lookup = function () use ( &$calls ) {
			++$calls;

			return 'DE';
		};

		LookupCache::remember( 'country', '203.0.113.0', $lookup );
		LookupCache::remember( 'country', '203.0.113.0', $lookup );

		self::assertSame( 1, $calls, 'a rule registered twice must not look the same thing up twice' );
	}

	public function test_caching_can_be_switched_off_through_the_filter(): void {
		expectApplied( 'antispam_bee_lookup_cache_ttl' )->andReturn( 0 );

		$calls = 0;
		$lookup = function () use ( &$calls ) {
			++$calls;

			return 'DE';
		};

		LookupCache::remember( 'country', '203.0.113.0', $lookup );
		LookupCache::flush_memo();
		LookupCache::remember( 'country', '203.0.113.0', $lookup );

		self::assertSame( 2, $calls, 'a ttl of zero should stop anything being stored' );
	}

	/**
	 * `get_transient()` reports a miss as `false`, so storing a value on its own
	 * makes a lookup that legitimately returns `false` re-run for ever — quietly
	 * removing the protection this class exists to provide.
	 *
	 * @dataProvider falsy_results
	 *
	 * @param mixed $result A value a lookup may legitimately return.
	 */
	public function test_a_falsy_result_is_cached_like_any_other( $result ): void {
		$calls  = 0;
		$lookup = function () use ( &$calls, $result ) {
			++$calls;

			return $result;
		};

		self::assertSame( $result, LookupCache::remember( 'gravatar', 'subject', $lookup ) );

		LookupCache::flush_memo();
		self::assertSame(
			$result,
			LookupCache::remember( 'gravatar', 'subject', $lookup ),
			'the stored value should round-trip unchanged'
		);

		self::assertSame( 1, $calls, 'the lookup should not run again' );
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public function falsy_results(): array {
		return [
			'false'        => [ false ],
			'empty string' => [ '' ],
			'zero'         => [ 0 ],
			'empty array'  => [ [] ],
		];
	}

	/**
	 * The transient name is written to the options table, and the subjects are a
	 * visitor's network and a commenter's email. An unkeyed digest over either is
	 * small enough to reverse, so the name must not contain one.
	 */
	public function test_the_stored_name_is_not_a_plain_digest_of_the_subject(): void {
		LookupCache::remember( 'country', '203.0.113.0', static fn() => 'DE' );

		$names = array_keys( $GLOBALS['asb_test_transients'] );
		self::assertCount( 1, $names );
		self::assertStringNotContainsString(
			md5( '203.0.113.0' ),
			$names[0],
			'the subject must not be recoverable from the stored name'
		);
	}
}
