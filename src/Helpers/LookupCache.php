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
	 * @param callable $lookup    Performs the lookup. Returning `null` marks it as failed,
	 *                            which is cached too, for a shorter time.
	 *
	 * @return mixed The lookup result, or null when it failed.
	 */
	public static function remember( string $namespace, string $key, callable $lookup ) {
		/*
		 * Keyed digest, not a plain hash. The transient name is written to the
		 * options table, and the subjects are a visitor's network and a commenter's
		 * email address: an unkeyed MD5 over either is small enough to reverse by
		 * brute force, which would turn the row into a log of who interacted with
		 * the site. Keying it on the site's salt also means the value being looked
		 * up cannot be used to predict — or collide with — a cache entry.
		 */
		$name = self::PREFIX . $namespace . '_' . substr( hash_hmac( 'sha256', $key, wp_salt( 'nonce' ) ), 0, 32 );

		if ( array_key_exists( $name, self::$memo ) ) {
			return self::$memo[ $name ];
		}

		/*
		 * Stored inside an envelope rather than on its own. `get_transient()`
		 * reports a miss as `false`, so a lookup that legitimately returns `false`
		 * would be written and then read back as a miss for ever, quietly removing
		 * the protection this class exists to provide. Anything that is not the
		 * envelope is a miss; anything inside it round-trips, including `false`,
		 * `''`, `0` and `null`.
		 */
		$cached = get_transient( $name );
		if ( is_array( $cached ) && array_key_exists( 'value', $cached ) ) {
			self::$memo[ $name ] = $cached['value'];

			return $cached['value'];
		}

		/*
		 * A failure is remembered for the whole kind of lookup, not just this
		 * subject. Caching it per subject only helps when the same subject comes
		 * back, which is true of an address or an email but not of a comment: every
		 * one is different text, so an outage would cost a full request timeout on
		 * every submission — the very thing this cache exists to prevent.
		 */
		$cooldown = self::PREFIX . $namespace . '_unavailable';
		if ( false !== get_transient( $cooldown ) ) {
			self::$memo[ $name ] = null;

			return null;
		}

		$value = $lookup();
		$ttl   = self::ttl( $namespace, null === $value );

		if ( null === $value && $ttl > 0 ) {
			set_transient( $cooldown, 1, $ttl );
		}

		if ( $ttl > 0 ) {
			set_transient( $name, [ 'value' => $value ], $ttl );
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
