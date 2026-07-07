<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Rules\Base;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Filters\expectApplied;

if ( ! defined( 'AntispamBee\PLUGIN_PATH' ) ) {
	define( 'AntispamBee\PLUGIN_PATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

/**
 * Non-final test rule with a configurable score that counts its calls.
 */
class RulesTestNonFinalRule extends Base {
	protected static $slug = 'test-non-final';

	public static $verify_calls = 0;
	public static $score        = 0;

	public static function verify( array $item ): int {
		++self::$verify_calls;

		return self::$score;
	}

	public static function get_name(): string {
		return 'Non-final test rule';
	}
}

/**
 * Final test rule with a configurable score that counts its calls.
 */
class RulesTestFinalRule extends Base {
	protected static $slug     = 'test-final';
	protected static $is_final = true;

	public static $verify_calls = 0;
	public static $score        = 0;

	public static function verify( array $item ): int {
		++self::$verify_calls;

		return self::$score;
	}

	public static function get_name(): string {
		return 'Final test rule';
	}
}

/**
 * Unit tests for {@see Rules::apply()}.
 */
class RulesTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		RulesTestNonFinalRule::$verify_calls = 0;
		RulesTestNonFinalRule::$score        = 0;
		RulesTestFinalRule::$verify_calls    = 0;
		RulesTestFinalRule::$score           = 0;
	}

	/**
	 * Register the two test rules, non-final first, to prove that final
	 * rules are checked before the others regardless of registration order.
	 */
	private function register_test_rules(): void {
		expectApplied( 'antispam_bee_rules' )
			->andReturn( [ RulesTestNonFinalRule::class, RulesTestFinalRule::class ] );
	}

	public function test_final_rule_hit_marks_spam_without_checking_remaining_rules() {
		$this->register_test_rules();
		RulesTestFinalRule::$score    = 999;
		RulesTestNonFinalRule::$score = -1;

		$rules = new Rules( ContentTypeHelper::COMMENT_TYPE );

		self::assertTrue( $rules->apply( [] ), 'positive final rule should mark the item as spam' );
		self::assertSame( 1, RulesTestFinalRule::$verify_calls, 'final rule should have been checked' );
		self::assertSame( 0, RulesTestNonFinalRule::$verify_calls, 'non-final rule should not run after a final hit' );
		self::assertSame( [ 'test-final' ], $rules->get_spam_reasons(), 'only the final rule should be recorded as reason' );
	}

	public function test_final_rule_without_hit_does_not_short_circuit() {
		$this->register_test_rules();
		RulesTestFinalRule::$score    = 0;
		RulesTestNonFinalRule::$score = 1;

		$rules = new Rules( ContentTypeHelper::COMMENT_TYPE );

		self::assertTrue( $rules->apply( [] ), 'positive overall score should mark the item as spam' );
		self::assertSame( 1, RulesTestFinalRule::$verify_calls, 'final rule should have been checked' );
		self::assertSame( 1, RulesTestNonFinalRule::$verify_calls, 'non-final rule should run when no final rule hits' );
		self::assertSame( [ 'test-non-final' ], $rules->get_spam_reasons(), 'unexpected spam reasons' );
		self::assertSame( [ 'test-final' ], $rules->get_no_spam_reasons(), 'unexpected no-spam reasons' );
	}

	public function test_sorting_can_be_disabled_via_filter() {
		$this->register_test_rules();
		RulesTestFinalRule::$score    = 999;
		RulesTestNonFinalRule::$score = 0;

		expectApplied( 'antispam_bee_sort_final_rules_first' )
			->once()
			->andReturn( false );

		$rules = new Rules( ContentTypeHelper::COMMENT_TYPE );

		self::assertTrue( $rules->apply( [] ), 'positive final rule should still mark the item as spam' );
		self::assertSame( 1, RulesTestNonFinalRule::$verify_calls, 'non-final rule should run first when sorting is disabled' );
		self::assertSame( 1, RulesTestFinalRule::$verify_calls, 'final rule should still be checked' );
	}

	public function test_negative_overall_score_is_not_spam() {
		$this->register_test_rules();
		RulesTestFinalRule::$score    = 0;
		RulesTestNonFinalRule::$score = -1;

		$rules = new Rules( ContentTypeHelper::COMMENT_TYPE );

		self::assertFalse( $rules->apply( [] ), 'negative overall score should not mark the item as spam' );
		self::assertSame( 1, RulesTestFinalRule::$verify_calls, 'final rule should have been checked' );
		self::assertSame( 1, RulesTestNonFinalRule::$verify_calls, 'non-final rule should have been checked' );
	}
}
