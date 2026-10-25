<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\PostProcessors\DeleteForReasons;
use AntispamBee\Rules\Base;
use AntispamBee\Rules\ControllableBase;
use AntispamBee\Interfaces\SpamReason;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Filters\expectApplied;

if ( ! defined( 'AntispamBee\PLUGIN_PATH' ) ) {
	define( 'AntispamBee\PLUGIN_PATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
	define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'antispam_bee.php' );
}

/**
 * Test rule with a fixed activation state.
 */
class DeleteForReasonsTestRule extends ControllableBase implements SpamReason {
	protected static $slug   = 'test-active';
	protected static $active = true;

	/**
	 * Control the activation state from a test.
	 *
	 * @param bool $active The new activation state.
	 */
	public static function set_active( bool $active ): void {
		static::$active = $active;
	}

	public static function is_active( string $reaction_type ) {
		return static::$active ? 'on' : null;
	}

	public static function verify( array $item ): int {
		return 0;
	}

	public static function get_name(): string {
		return 'Test reason';
	}

	public static function get_label(): ?string {
		return null;
	}

	public static function get_description(): ?string {
		return null;
	}

	public static function get_reason_text(): string {
		return 'Test reason';
	}
}

/**
 * Test rule that is always inactive, for controllable rules.
 */
class DeleteForReasonsTestInactiveRule extends ControllableBase implements SpamReason {
	protected static $slug = 'test-inactive';

	public static function is_active( string $reaction_type ) {
		return null;
	}

	public static function verify( array $item ): int {
		return 0;
	}

	public static function get_name(): string {
		return 'Inactive test reason';
	}

	public static function get_label(): ?string {
		return null;
	}

	public static function get_description(): ?string {
		return null;
	}

	public static function get_reason_text(): string {
		return 'Inactive test reason';
	}
}

/**
 * Test rule that is not controllable, therefore always considered active.
 */
class DeleteForReasonsTestNonControllableRule extends Base implements SpamReason {
	protected static $slug = 'test-fixed';

	public static function verify( array $item ): int {
		return 0;
	}

	public static function get_name(): string {
		return 'Non-controllable test reason';
	}

	public static function get_reason_text(): string {
		return 'Non-controllable test reason';
	}
}

/**
 * Test rule that is active for comment posts but inactive for linkbacks.
 */
class DeleteForReasonsTestTypedRule extends ControllableBase implements SpamReason {
	protected static $slug = 'test-typed';

	public static function is_active( string $reaction_type ) {
		return ContentTypeHelper::COMMENT_TYPE === $reaction_type ? 'on' : null;
	}

	public static function verify( array $item ): int {
		return 0;
	}

	public static function get_name(): string {
		return 'Typed test reason';
	}

	public static function get_label(): ?string {
		return null;
	}

	public static function get_description(): ?string {
		return null;
	}

	public static function get_reason_text(): string {
		return 'Typed test reason';
	}
}

/**
 * Unit tests for {@see DeleteForReasons}.
 */
class DeleteForReasonsTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		DeleteForReasonsTestRule::set_active( true );
	}

	private function register_reason_rules(): void {
		expectApplied( 'antispam_bee_rules' )
			->andReturn(
				[
					DeleteForReasonsTestRule::class,
					DeleteForReasonsTestInactiveRule::class,
					DeleteForReasonsTestNonControllableRule::class,
				]
			);
	}

	private function register_non_controllable_rules(): void {
		expectApplied( 'antispam_bee_rules' )
			->andReturn( [ DeleteForReasonsTestNonControllableRule::class ] );
	}

	private function register_typed_rules(): void {
		expectApplied( 'antispam_bee_rules' )
			->andReturn( [ DeleteForReasonsTestTypedRule::class ] );
	}

	public function test_disabled_keys_marks_only_inactive_controllable_rules(): void {
		$this->register_reason_rules();

		$options = DeleteForReasons::get_options();

		self::assertNotEmpty( $options, 'expected one option set per supported reaction type' );

		foreach ( $options as $option ) {
			$disabled = $option['disabled_keys'] ?? [];

			self::assertIsArray( $disabled, 'disabled_keys should always be present as an array' );

			self::assertArrayHasKey(
				'test-inactive',
				$disabled,
				'inactive controllable rule should be disabled'
			);
			self::assertArrayNotHasKey(
				'test-active',
				$disabled,
				'active controllable rule should not be disabled'
			);
			self::assertArrayNotHasKey(
				'test-fixed',
				$disabled,
				'non-controllable rule should never be disabled'
			);
		}
	}

	public function test_active_controllable_rule_never_disabled(): void {
		$this->register_reason_rules();
		DeleteForReasonsTestRule::set_active( true );

		$options = DeleteForReasons::get_options();

		foreach ( $options as $option ) {
			self::assertArrayNotHasKey(
				'test-active',
				$option['disabled_keys'],
				'an active controllable rule should not be disabled'
			);
			self::assertArrayHasKey(
				'test-inactive',
				$option['disabled_keys'],
				'the always-inactive rule should stay disabled'
			);
		}
	}

	public function test_disabled_keys_is_empty_when_no_rule_is_controllable(): void {
		$this->register_non_controllable_rules();

		$options = DeleteForReasons::get_options();

		foreach ( $options as $option ) {
			self::assertSame( [], $option['disabled_keys'], 'no controllable rule means nothing should be disabled' );
		}
	}

	public function test_disabled_keys_are_built_per_reaction_type(): void {
		$this->register_typed_rules();

		$options = DeleteForReasons::get_options();
		$by_type = [];
		foreach ( $options as $option ) {
			$by_type[ $option['valid_for'] ] = $option['disabled_keys'];
		}

		self::assertArrayNotHasKey(
			'test-typed',
			$by_type[ ContentTypeHelper::COMMENT_TYPE ],
			'a rule active for comments should not be disabled there'
		);
		self::assertArrayHasKey(
			'test-typed',
			$by_type[ ContentTypeHelper::LINKBACK_TYPE ],
			'the same rule inactive for linkbacks should be disabled there'
		);
	}

	public function test_sanitize_still_accepts_a_stored_value_for_a_disabled_key(): void {
		$this->register_reason_rules();

		$options  = DeleteForReasons::get_options();
		$sanitize = $options[0]['sanitize'];
		$posted   = [ 'test-inactive' => 'on', 'test-active' => 'on' ];

		self::assertSame(
			[ 'test-inactive', 'test-active' ],
			array_keys( (array) $sanitize( $posted ) ),
			'a disabled rule that was previously stored should survive the round-trip'
		);
	}

	public function test_reason_list_is_unchanged(): void {
		$this->register_reason_rules();

		$options = DeleteForReasons::get_options();

		$expected_slugs = [ 'test-active', 'test-inactive', 'test-fixed' ];

		foreach ( $options as $option ) {
			self::assertSame(
				$expected_slugs,
				array_keys( $option['options'] ),
				'all non-invisible rules should still be offered, regardless of active state'
			);
		}
	}
}
