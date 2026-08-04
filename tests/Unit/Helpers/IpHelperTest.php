<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\IpHelper;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see IpHelper}.
 *
 * @backupGlobals enabled
 */
class IpHelperTest extends TestCase {

	public function test_get_client_ip_uses_remote_addr(): void {
		global $_SERVER;

		when( 'wp_unslash' )->returnArg();

		$_SERVER['REMOTE_ADDR']          = '192.0.2.1';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';

		self::assertSame( '192.0.2.1', IpHelper::get_client_ip(), 'REMOTE_ADDR should be the default IP source' );
	}

	/**
	 * @throws ExpectationArgsRequired
	 */
	public function test_get_client_ip_filter_can_override(): void {
		global $_SERVER;

		when( 'wp_unslash' )->returnArg();

		$_SERVER['REMOTE_ADDR'] = '192.0.2.1';

		expectApplied( 'pre_comment_user_ip' )
			->once()
			->with( '192.0.2.1' )
			->andReturn( '192.0.2.2' );

		self::assertSame( '192.0.2.2', IpHelper::get_client_ip(), 'pre_comment_user_ip filter should override the IP' );
	}

	/**
	 * @dataProvider anonymize_ip_provider
	 */
	public function test_anonymize_ip( string $ip, string $expected, string $message ): void {
		self::assertSame( $expected, IpHelper::anonymize_ip( $ip ), $message );
	}

	/**
	 * @dataProvider is_global_ip_provider
	 */
	public function test_is_global_ip( string $ip, bool $expected, string $message ): void {
		self::assertSame( $expected, IpHelper::is_global_ip( $ip ), $message );
	}

	public function is_global_ip_provider(): array {
		return [
			[ '198.51.100.42', true, 'a global IPv4 address should be global' ],
			// Deliberately from `3fff::/20` (RFC 9637) rather than the older `2001:db8::/32`
			// (RFC 3849): PHP 7.4 still rejects the latter as a reserved range, while PHP 8.3
			// and later report it as global, so it cannot stand in for a routable address
			// across the supported versions.
			[ '3fff:85a3:1234::5', true, 'a global IPv6 address should be global' ],
			// `FILTER_FLAG_NO_RES_RANGE` rejects all of `::ffff:0:0/96`, so the mapped
			// IPv4 address has to be unwrapped before it is checked.
			[ '::ffff:198.51.100.42', true, 'an IPv4-mapped global address should be global' ],
			[ '127.0.0.1', false, 'the IPv4 loopback address should not be global' ],
			[ '::1', false, 'the IPv6 loopback address should not be global' ],
			[ '10.11.12.13', false, 'a private IPv4 address should not be global' ],
			[ '172.16.0.1', false, 'another private IPv4 address should not be global' ],
			[ '192.168.1.50', false, 'a third private IPv4 address should not be global' ],
			[ '169.254.1.1', false, 'a link-local IPv4 address should not be global' ],
			[ 'fe80::1', false, 'a link-local IPv6 address should not be global' ],
			[ 'fc00::1', false, 'a unique local IPv6 address should not be global' ],
			[ '::ffff:10.0.0.1', false, 'an IPv4-mapped private address should not be global' ],
			[ '', false, 'empty input should not be global' ],
			[ 'no-ip-at-all', false, 'non-IP input should not be global' ],
		];
	}

	public function anonymize_ip_provider(): array {
		return [
			[ '198.51.100.42', '198.51.100.0', 'IPv4 address should be truncated to a /24 network' ],
			[ '10.11.12.13', '10.11.12.0', 'IPv4 host bits should be zeroed out' ],
			[ '2001:db8:85a3:8d3:1319:8a2e:370:7348', '2001:db8:85a3::', 'IPv6 address should be truncated to a /48 network' ],
			[ '2001:db8:85a3::8a2e:370:7334', '2001:db8:85a3::', 'compressed IPv6 address should be truncated to a /48 network' ],
			[ 'fd02:8109:aa00:1234::5', 'fd02:8109:aa00::', 'the fourth group should be dropped whole, not truncated to a /56' ],
			[ '2001:db8::1', '2001:db8::', 'IPv6 address shorter than the mask should only lose its host bits' ],
			[ '::1', '::', 'the IPv6 loopback address should be masked, not rejected' ],
			[ '0:0:0:0:0:0:0:1', '::', 'the result should not depend on the notation of the address' ],
			[ 'fe80::1', 'fe80::', 'a link-local IPv6 address should be masked, not rejected' ],
			[ '::ffff:192.0.2.123', '::ffff:192.0.2.0', 'an IPv4-mapped IPv6 address should be masked like an IPv4 address' ],
			[ '', '', 'empty input should result in an empty string' ],
			[ 'no-ip-at-all', '', 'non-IP input should result in an empty string' ],
		];
	}
}
