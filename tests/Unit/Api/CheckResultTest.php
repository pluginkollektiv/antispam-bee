<?php

namespace AntispamBee\Tests\Unit\Api;

use AntispamBee\Api\CheckResult;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see CheckResult}.
 */
class CheckResultTest extends TestCase {

	public function test_exposes_the_check_outcome(): void {
		$payload = [
			'reaction_type' => 'test_reaction',
			'body'          => 'Spam',
		];
		$result  = new CheckResult( true, [ 'asb-bbcode' ], $payload );

		self::assertTrue( $result->is_spam(), 'the item should be reported as spam' );
		self::assertTrue( $result->was_evaluated(), 'a constructed result should be evaluated by default' );
		self::assertSame( [ 'asb-bbcode' ], $result->get_reasons(), 'the reason slugs should be exposed' );
		self::assertSame( $payload, $result->get_payload(), 'the payload should be exposed unchanged' );
	}

	public function test_not_evaluated_reports_no_spam_and_no_evaluation(): void {
		$payload = [ 'reaction_type' => 'test_reaction' ];
		$result  = CheckResult::not_evaluated( $payload );

		self::assertFalse( $result->is_spam(), 'an unevaluated item should not be spam' );
		self::assertFalse( $result->was_evaluated(), 'the result should report that nothing was evaluated' );
		self::assertSame( [], $result->get_reasons(), 'an unevaluated item should carry no reasons' );
		self::assertSame( $payload, $result->get_payload(), 'the payload should still be exposed' );
	}

	/**
	 * Without reasons there is nothing to resolve, so the helper — which is only
	 * populated on `init` — must not be consulted at all.
	 */
	public function test_reason_texts_are_empty_without_reasons(): void {
		$result = new CheckResult( false, [], [] );

		self::assertSame( [], $result->get_reason_texts(), 'no reasons should resolve to no texts' );
	}

	public function test_reason_texts_are_resolved_via_the_helper(): void {
		when( 'esc_html' )->returnArg();
		when( 'esc_html_x' )->returnArg();

		$result = new CheckResult( true, [ 'unregistered-rule' ], [] );

		self::assertSame(
			[ 'Unknown rule: unregistered-rule' ],
			$result->get_reason_texts(),
			'reason texts should be resolved through SpamReasonTextHelper'
		);
	}
}
