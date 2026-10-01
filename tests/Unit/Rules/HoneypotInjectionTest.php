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

	/**
	 * While output buffering is on, the whole page is injected into, so the form
	 * field filter must not inject a second time.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_field_filter_leaves_the_form_to_the_output_buffer(): void {
		$settings = mock( 'alias:' . \AntispamBee\Helpers\Settings::class );
		$settings->allows( 'get_option' )->andReturns( 'on' );

		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->expects( 'inject' )->never();
		$honeypot_helper->expects( 'record_injection' )->never();

		$markup = '<textarea id="comment" name="comment"></textarea>';

		self::assertSame( $markup, Honeypot::inject_honeypot_field( $markup ) );
	}

	/**
	 * Every front-end page passes through the buffer, and one without a comment field
	 * had no form to inject into, so it must not count as a failed injection.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_page_without_a_comment_field_is_left_alone(): void {
		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->expects( 'inject' )->never();
		$honeypot_helper->expects( 'record_injection' )->never();

		$page = '<html><body><form><textarea name="comments"></textarea></form></body></html>';

		self::assertSame( $page, Honeypot::inject_into_page( $page ) );
	}

	/**
	 * A page with a comment field is injected into by field name, and the outcome is
	 * recorded as for the form field filter.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_a_page_with_a_comment_field_is_injected_into_and_recorded(): void {
		$page = '<html><body><form><textarea name=comment rows="8"></textarea></form></body></html>';

		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->expects( 'inject' )
			->once()
			->with(
				$page,
				[
					'field_id'   => 'comment',
					'field_name' => 'comment',
				]
			)
			->andReturn( $page . '<!-- injected -->' );
		$honeypot_helper->expects( 'record_injection' )->once()->with( true );

		self::assertSame( $page . '<!-- injected -->', Honeypot::inject_into_page( $page ) );
	}
}
