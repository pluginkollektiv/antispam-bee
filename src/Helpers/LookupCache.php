<?php
/**
 * Cache for the outbound lookups rules perform.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Cache for the outbound lookups rules perform.
 *
 * Several rules ask a third-party service about a submission while it is being
 * checked, on a hook that runs before core's flood control. Without a cache,
 * every comment — including every attempt by someone posting in bulk — turns
 * into a fresh outbound request, which burns the site's API quota and lets an
 * unauthenticated visitor decide how much traffic the server makes.
 */
class LookupCache {

	/**
	 * Prefix for the transient names.
	 *
	 * @var string
	 */
	private const PREFIX = 'asb_lookup_';

	/**
	 * Results already looked up in this request, keyed by transient name.
	 *
	 * A rule registered twice is evaluated twice, so the same lookup can happen
	 * more than once within a single request, before any transient written at
	 * the end of the first one is visible.
	 *
	 * @var array<string, mixed>
	 */
	private static $memo = [];

	/**
	 * Return a cached lookup result, performing the lookup when there is none.
	 *
	 * @param string   $namespace Identifies the kind of lookup, e.g. `country`.
	 * @param string   $key       Identifies the subject of the lookup. Hashed before use.
	 * @param callable $lookup    Performs the lookup. Returns `null` to indicate failure.
	 *
	 * @return mixed The lookup result, or null when it failed.
	 */
	public static function remember( string $namespace, string $key, callable $lookup ) {
		$name = self::PREFIX . $namespace . '_' . md5( $key );

		if ( array_key_exists( $name, self::$memo ) ) {
			return self::$memo[ $name ];
		}

		$cached = get_transient( $name );
		if ( false !== $cached ) {
			// A failed lookup is stored as an empty string, so it is cached too.
			$value              = '' === $cached ? null : $cached;
			self::$memo[ $name ] = $value;

			return $value;
		}

		$value = $lookup();
		$ttl   = self::ttl( $namespace, null === $value );

		if ( $ttl > 0 ) {
			set_transient( $name, null === $value ? '' : $value, $ttl );
		}

		self::$memo[ $name ] = $value;

		return $value;
	}

	/**
	 * Determine how long a lookup result may be reused.
	 *
	 * @param string $namespace Identifies the kind of lookup.
	 * @param bool   $failed    Whether the lookup failed.
	 *
	 * @return int Lifetime in seconds. Zero or less disables caching.
	 */
	private static function ttl( string $namespace, bool $failed ): int {
		$default = $failed ? 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;

		if ( ! $failed && 'lang' === $namespace ) {
			/*
			 * Keyed on a digest of the comment, so this entry is a fingerprint of
			 * text the site may have chosen not to store — the delete-instead-of-
			 * mark setting never writes the comment at all. Kept only long enough
			 * to absorb a burst.
			 */
			$default = 15 * MINUTE_IN_SECONDS;
		}

		/**
		 * Filters how long the result of an outbound rule lookup is reused.
		 *
		 * Returning zero or less disables caching for that lookup, which means
		 * every reaction performs its own request again.
		 *
		 * @since 3.0.0
		 *
		 * @param int    $ttl       Lifetime in seconds.
		 * @param string $namespace The kind of lookup: `country`, `lang` or `gravatar`.
		 * @param bool   $failed    Whether the lookup failed and is being cached negatively.
		 */
		return (int) apply_filters( 'antispam_bee_lookup_cache_ttl', $default, $namespace, $failed );
	}

	/**
	 * Forget everything remembered during this request.
	 *
	 * Only needed by tests; the memo is per-request and dies with it.
	 *
	 * @return void
	 */
	public static function flush_memo(): void {
		self::$memo = [];
	}
}
