<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\GeneralOptions\Uninstall;
use AntispamBee\Handlers\PluginStateChangeHandler;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see PluginStateChangeHandler}.
 */
class PluginStateChangeHandlerTest extends TestCase {

	/**
	 * Every site of a network is cleaned, not just the first page of them.
	 *
	 * @return void
	 */
	public function test_uninstall_visits_every_site_of_a_large_network() {
		$all_sites = range( 1, 250 );
		$queried   = [];
		$visited   = [];

		stubs( [ 'is_multisite' => true ] );

		when( 'get_sites' )->alias(
			function ( array $args ) use ( $all_sites, &$queried ) {
				$queried[] = $args;

				return array_slice( $all_sites, $args['offset'], $args['number'] );
			}
		);
		when( 'switch_to_blog' )->alias(
			function ( $site_id ) use ( &$visited ) {
				$visited[] = $site_id;
			}
		);
		when( 'restore_current_blog' )->justReturn( true );
		when( 'delete_option' )->justReturn( true );

		mock( 'overload:' . Uninstall::class )
			->allows( 'is_active' )
			->andReturn( true );

		PluginStateChangeHandler::uninstall();

		self::assertSame( $all_sites, $visited, 'Every site of the network is cleaned.' );
		self::assertSame( [ 0, 100, 200 ], array_column( $queried, 'offset' ), 'The site list is walked in pages.' );
	}
}
