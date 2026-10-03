<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\TooFastSubmit;
use function Brain\Monkey\Functions\when;

class TooFastSubmitTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( TooFastSubmit::class, 'asb-too-fast-submit' );
	}

	protected function set_up(): void {
		parent::set_up();

		when( 'wp_unslash' )->returnArg();
	}

	protected function tearDown(): void {
		unset( $_POST['ab_init_time'], $_POST['ab_elapsed_time'] );

		parent::tearDown();
	}

	public function test_verify_without_either_field() {
		self::assertSame( 0, TooFastSubmit::verify( [] ), 'Unexpected result without ab_init_time or ab_elapsed_time' );
	}

	/*
	 * ab_elapsed_time present: the client measured the dwell time itself, entirely
	 * with its own clock, so none of these can be affected by clock skew.
	 */

	public function test_verify_elapsed_time_too_fast() {
		$_POST['ab_elapsed_time'] = '0';

		self::assertSame( 1, TooFastSubmit::verify( [] ), 'Unexpected result for no measured dwell time' );
	}

	public function test_verify_elapsed_time_slow_enough() {
		$_POST['ab_elapsed_time'] = '10';

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'Unexpected result for enough measured dwell time' );
	}

	public function test_verify_elapsed_time_is_preferred_over_init_time() {
		// A stale ab_init_time that would, on its own, fail open or flag as spam must
		// not change the result once ab_elapsed_time is present.
		$_POST['ab_init_time']    = (string) ( time() + 3600 );
		$_POST['ab_elapsed_time'] = '10';

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'ab_elapsed_time must be used instead of ab_init_time when both are present' );
	}

	public function test_verify_negative_elapsed_time_fails_open() {
		// Impossible for a correctly functioning client (the script uses
		// performance.now(), which cannot go backwards) - a sign of a forged or
		// malfunctioning value, not a fast submission.
		$_POST['ab_elapsed_time'] = '-600';

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'A negative dwell time must fail open, not be treated as fast' );
	}

	public function test_verify_empty_elapsed_time_falls_back_to_init_time() {
		// The field is always rendered; it is only filled in by the submit listener,
		// so an empty string means JavaScript did not run and the fallback applies.
		$_POST['ab_elapsed_time'] = '';
		$_POST['ab_init_time']    = (string) ( time() - 10 );

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'An empty ab_elapsed_time must fall back to the ab_init_time comparison' );
	}

	/*
	 * ab_elapsed_time absent (a form cached from before it existed, or a client that
	 * does not run JavaScript at all): fall back to the server-clock comparison.
	 */

	public function test_verify_init_time_too_fast() {
		$_POST['ab_init_time'] = (string) time();

		self::assertSame( 1, TooFastSubmit::verify( [] ), 'Unexpected result for a submission right after render' );
	}

	public function test_verify_init_time_slow_enough() {
		$_POST['ab_init_time'] = (string) ( time() - 10 );

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'Unexpected result for a submission with enough dwell time' );
	}

	public function test_verify_init_time_client_clock_ahead_of_server_fails_open() {
		// A page cached from before ab_elapsed_time existed still carries the old
		// script, which rewrites ab_init_time to the client's own clock. Comparing
		// that against the server's time() would otherwise make every submission
		// from a client whose clock runs ahead look impossibly fast.
		$_POST['ab_init_time'] = (string) ( time() + 3600 );

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'A future timestamp must fail open, not be treated as fast' );
	}

	public function test_verify_init_time_fails_open_on_any_negative_computed_dwell_time() {
		// Even a 1 second future timestamp must fail open rather than being treated
		// as "too fast": the computed dwell time is negative either way, and there is
		// no safe tolerance band that does not also misclassify some genuine skew.
		$_POST['ab_init_time'] = (string) ( time() + 1 );

		self::assertSame( 0, TooFastSubmit::verify( [] ), 'A negative computed dwell time must fail open, not be treated as fast' );
	}
}
