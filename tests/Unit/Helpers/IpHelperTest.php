<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\IpHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Filters\expectApplied;

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
	 * Anonymizing an IP should keep only its first two parts.
	 *
	 * @dataProvider provide_ips_to_anonymize
	 *
	 * @param string $ip       The original IP.
	 * @param string $expected The expected anonymized IP.
	 */
	public function test_anonymize_ip_keeps_only_first_two_parts( string $ip, string $expected ): void {
		self::assertSame( $expected, IpHelper::anonymize_ip( $ip ), 'Only the first two parts of the IP should remain' );
	}

	/**
	 * Data provider for {@see test_anonymize_ip_keeps_only_first_two_parts}.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function provide_ips_to_anonymize(): array {
		return array(
			'IPv4 normal'     => array( '192.168.1.1', '192.168.0.0' ),
			'IPv4 short'      => array( '10.0.0.1', '10.0.0.0' ),
			'IPv6 full'       => array( '2001:db8:85a3:8d3:1319:8a2e:370:7348', '2001:db8::' ),
			'IPv6 compressed' => array( '2001:db8::1', '2001:db8::' ),
		);
	}
}
