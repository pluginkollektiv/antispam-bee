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
}
