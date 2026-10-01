<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\Honeypot;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see Honeypot::inject_honeypot_field()}.
 *
 * Kept apart from {@see HoneypotTest}, whose constructor does not support running
 * a test in a separate process, which the class mocks below require.
 */
class HoneypotInjectionTest extends TestCase {

	/**
	 * Rendering the comment field records whether the honeypot made it into the
	 * form, which is what `precheck()` relies on to hold the gate.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_inject_honeypot_field_records_the_injection_outcome(): void {
		$settings = mock( 'alias:' . \AntispamBee\Helpers\Settings::class );
		$settings->allows( 'get_option' )->andReturns( true );

		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->allows( 'inject' )->andReturnUsing(
			function ( string $markup ) {
				return false === strpos( $markup, 'id="comment"' ) ? $markup : $markup . '<textarea name="hp"></textarea>';
			}
		);
		$honeypot_helper->expects( 'record_injection' )->once()->with( true );
		$honeypot_helper->expects( 'record_injection' )->once()->with( false );

		Honeypot::inject_honeypot_field( '<textarea id="comment" name="comment"></textarea>' );
		Honeypot::inject_honeypot_field( '<input id="comment-text" name="comment" />' );
	}
}
