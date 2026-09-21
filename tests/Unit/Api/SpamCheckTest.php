<?php

namespace AntispamBee\Tests\Unit\Api;

use AntispamBee\Api\SpamCheck;
use AntispamBee\Rules\Base;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\PLUGIN_PATH' ) ) {
	define( 'AntispamBee\PLUGIN_PATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

/**
 * Test rule that records the payload it was given.
 */
class SpamCheckTestRule extends Base {
	protected static $slug            = 'test-spam-check';
	protected static $supported_types = [ 'test_reaction' ];

	public static $score = 0;

	/**
	 * The payload of the last verification.
	 *
	 * @var array<string, mixed>
	 */
	public static $item = [];

	public static function verify( array $item ): int {
		self::$item = $item;

		return self::$score;
	}

	public static function get_name(): string {
		return 'Spam check test rule';
	}
}

/**
 * Unit tests for {@see SpamCheck}.
 *
 * @backupGlobals enabled
 */
class SpamCheckTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		SpamCheckTestRule::$score = 0;
		SpamCheckTestRule::$item  = [];

		when( 'wp_unslash' )->returnArg();
		when( 'sanitize_text_field' )->returnArg();
		when( 'wp_parse_url' )->alias(
			static function ( $url ) {
				return parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
			}
		);
	}

	/**
	 * Register the test rule as the only rule.
	 */
	private function register_test_rule(): void {
		expectApplied( 'antispam_bee_rules' )
			->andReturn( [ SpamCheckTestRule::class ] );
	}

	public function test_derives_host_from_url_and_defaults_request_attributes(): void {
		global $_SERVER;

		$this->register_test_rule();

		$_SERVER['REMOTE_ADDR']     = '192.0.2.5';
		$_SERVER['HTTP_USER_AGENT'] = 'Test user agent';

		SpamCheck::check(
			[
				'author' => 'Spammy McSpam',
				'body'   => 'Buy all the things',
				'email'  => 'spam@example.com',
				'url'    => 'https://spam.example.com/landing-page',
			],
			'test_reaction'
		);

		$item = SpamCheckTestRule::$item;

		self::assertSame( 'spam.example.com', $item['host'], 'host should be derived from the url' );
		self::assertSame( '192.0.2.5', $item['ip'], 'ip should default to the current request' );
		self::assertSame( 'Test user agent', $item['useragent'], 'useragent should default to the current request' );
		self::assertSame( 'test_reaction', $item['reaction_type'], 'reaction type should be part of the payload' );
		self::assertSame( 'Spammy McSpam', $item['author'], 'author should be passed through' );
		self::assertSame( 0, $item['post_id'], 'post_id should default to 0' );
	}

	public function test_explicit_host_is_not_overwritten(): void {
		$this->register_test_rule();

		SpamCheck::check(
			[
				'url'  => 'https://spam.example.com/landing-page',
				'host' => 'explicit.example.com',
			],
			'test_reaction'
		);

		self::assertSame( 'explicit.example.com', SpamCheckTestRule::$item['host'], 'an explicit host should win over the derived one' );
	}

	public function test_missing_attributes_default_to_empty_strings(): void {
		$this->register_test_rule();

		SpamCheck::check( [ 'body' => 'Just a body' ], 'test_reaction' );

		$item = SpamCheckTestRule::$item;

		self::assertSame( '', $item['author'], 'missing author should default to an empty string' );
		self::assertSame( '', $item['email'], 'missing email should default to an empty string' );
		self::assertSame( '', $item['url'], 'missing url should default to an empty string' );
		self::assertSame( '', $item['host'], 'host should stay empty without a url' );
	}

	public function test_reports_spam_with_reasons(): void {
		$this->register_test_rule();
		SpamCheckTestRule::$score = 1;

		$result = SpamCheck::check( [ 'body' => 'Spam' ], 'test_reaction' );

		self::assertTrue( $result->is_spam(), 'a positive score should be reported as spam' );
		self::assertTrue( $result->was_evaluated(), 'the item should be reported as evaluated' );
		self::assertSame( [ 'test-spam-check' ], $result->get_reasons(), 'the rule slug should be reported as reason' );
	}

	public function test_reports_ham_without_reasons(): void {
		$this->register_test_rule();
		SpamCheckTestRule::$score = -1;

		$result = SpamCheck::check( [ 'body' => 'Ham' ], 'test_reaction' );

		self::assertFalse( $result->is_spam(), 'a negative score should not be reported as spam' );
		self::assertTrue( $result->was_evaluated(), 'the item should be reported as evaluated' );
		self::assertSame( [], $result->get_reasons(), 'ham should not carry spam reasons' );
	}

	public function test_reports_not_evaluated_without_active_rule(): void {
		expectApplied( 'antispam_bee_rules' )->andReturn( [] );

		$result = SpamCheck::check( [ 'body' => 'Anything' ], 'test_reaction' );

		self::assertFalse( $result->is_spam(), 'an unevaluated item should not be reported as spam' );
		self::assertFalse( $result->was_evaluated(), 'an item is not evaluated when no rule is active' );
		self::assertSame( [], $result->get_reasons(), 'an unevaluated item should not carry reasons' );
		self::assertSame( 'test_reaction', $result->get_payload()['reaction_type'], 'the payload should be available even without evaluation' );
	}

	public function test_rules_of_other_reaction_types_are_not_applied(): void {
		$this->register_test_rule();

		$result = SpamCheck::check( [ 'body' => 'Anything' ], 'another_reaction' );

		self::assertFalse( $result->was_evaluated(), 'a rule that does not support the reaction type should not be applied' );
		self::assertSame( [], SpamCheckTestRule::$item, 'the rule should not have been called at all' );
	}

	public function test_has_active_rules(): void {
		$this->register_test_rule();

		self::assertTrue( SpamCheck::has_active_rules( 'test_reaction' ), 'the supported reaction type should report an active rule' );
		self::assertFalse( SpamCheck::has_active_rules( 'another_reaction' ), 'an unsupported reaction type should report no active rule' );
	}

	public function test_payload_can_be_filtered(): void {
		$this->register_test_rule();

		expectApplied( 'antispam_bee_api_payload' )
			->once()
			->andReturn(
				[
					'reaction_type' => 'test_reaction',
					'body'          => 'Replaced body',
				]
			);

		SpamCheck::check( [ 'body' => 'Original body' ], 'test_reaction' );

		self::assertSame( 'Replaced body', SpamCheckTestRule::$item['body'], 'the filter should be able to replace the payload' );
	}
}
