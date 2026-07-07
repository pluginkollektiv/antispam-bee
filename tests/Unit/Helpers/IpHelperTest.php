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

	public function anonymize_ip_provider(): array {
		return [
			[ '192.0.2.123', '192.0.0.0', 'IPv4 address should be truncated' ],
			[ '2001:db8:85a3::8a2e:370:7334', '2001:db8::', 'IPv6 address should be truncated' ],
			[ '', '', 'empty input should result in an empty string' ],
			[ '::1', '', 'unmatchable input should result in an empty string' ],
			[ 'no-ip-at-all', '', 'non-IP input should result in an empty string' ],
		];
	}
}
