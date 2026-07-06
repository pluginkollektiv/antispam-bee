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
	 * The bundled fallback masking should keep only the network portion: the
	 * first three octets for IPv4 (a /24) and the first three groups for IPv6
	 * (a /48). `wp_privacy_anonymize_ip()` is undefined in the unit test
	 * environment, so this exercises the fallback path.
	 *
	 * @dataProvider provide_ips_to_anonymize
	 *
	 * @param string $ip       The original IP.
	 * @param string $expected The expected anonymized IP.
	 */
	public function test_anonymize_ip_fallback_keeps_only_the_network_portion( string $ip, string $expected ): void {
		self::assertFalse( function_exists( 'wp_privacy_anonymize_ip' ), 'This test must run against the fallback masking' );
		self::assertSame( $expected, IpHelper::anonymize_ip( $ip ), 'Only the network portion of the IP should remain' );
	}

	/**
	 * When WordPress core's `wp_privacy_anonymize_ip()` is available, it should
	 * be used instead of the bundled fallback.
	 *
	 * Runs in a separate process so the stubbed function definition does not
	 * leak into the fallback tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_anonymize_ip_delegates_to_wordpress_core(): void {
		when( 'wp_privacy_anonymize_ip' )->justReturn( '203.0.113.0' );

		self::assertSame(
			'203.0.113.0',
			IpHelper::anonymize_ip( '203.0.113.42' ),
			'anonymize_ip should delegate to wp_privacy_anonymize_ip when it exists'
		);
	}

	/**
	 * Data provider for {@see test_anonymize_ip_fallback_keeps_only_the_network_portion}.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function provide_ips_to_anonymize(): array {
		return array(
			'IPv4 normal'     => array( '192.168.1.1', '192.168.1.0' ),
			'IPv4 short'      => array( '10.0.0.1', '10.0.0.0' ),
			'IPv6 full'       => array( '2001:db8:85a3:8d3:1319:8a2e:370:7348', '2001:db8:85a3::' ),
			'IPv6 compressed' => array( '2001:db8::1', '2001:db8::' ),
			'invalid input'   => array( 'not-an-ip', 'not-an-ip' ),
		);
	}
}
