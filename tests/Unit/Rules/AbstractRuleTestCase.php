<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Abstract test case for ASB rules.
 */
abstract class AbstractRuleTestCase extends TestCase {

	protected $rule;
	protected $slug;

	/**
	 * Test case constructor.
	 *
	 * @param mixed  $rule Rule class to test.
	 * @param string $slug Expected rule slug.
	 */
	public function __construct( $rule, string $slug ) {
		parent::__construct();

		$this->rule = $rule;
		$this->slug = $slug;
	}

	/**
	 * Test for expected slug.
	 * Might seem redundant, but we might just have forgotten about this...
	 *
	 * @return void
	 */
	public function test_slug() {
		self::assertSame( $this->slug, $this->rule::get_slug(), 'Unexpected slug' );
	}

	/**
	 * Test initialization.
	 * All rules should add themselves to the rule filter by default.
	 * Might be overwritten if a rule does special initialization.
	 *
	 * @return void
	 */
	public function test_init() {
		$this->rule::init();

		self::assertNotFalse(
			has_filter( 'antispam_bee_rules', [ $this->rule, 'add_rule' ] ),
			'The add_rule filter was not added'
		);
	}

	/**
	 * Generate a normalized test payload for a comment.
	 *
	 * @param int    $post_id      Post ID.
	 * @param string $author       Author name.
	 * @param string $author_email Author email.
	 * @param string $author_url   Author URL.
	 * @param string $author_ip    Author IP address.
	 * @param string $content      Comment content.
	 * @param string $agent        User agent.
	 *
	 * @return array<string, mixed> Normalized payload.
	 */
	protected static function make_comment(
		int $post_id = 1,
		string $author = 'Test Author',
		string $author_email = 'test.author@example.com',
		string $author_url = 'www.example.com',
		string $author_ip = '192.0.2.1',
		string $content = 'This is the base test comment.',
		string $agent = 'Mozilla/5.0'
	): array {
		return [
			'reaction_type' => ContentTypeHelper::COMMENT_TYPE,
			'post_id'       => $post_id,
			'author'        => $author,
			'email'         => $author_email,
			'url'           => $author_url,
			'ip'            => $author_ip,
			'body'          => $content,
			'useragent'     => $agent,
		];
	}
}
