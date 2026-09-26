<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\LookupCache;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;

/**
 * Unit tests for {@see LookupCache}.
 */
class LookupCacheTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		$GLOBALS['asb_test_transients'] = [];
		LookupCache::flush_memo();
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
}
