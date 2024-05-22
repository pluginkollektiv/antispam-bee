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
		self::assertSame( $this->slug, $this->rule::get_slug(), 'unexpected slug' );
	}

	/**
	 * Test initialization.
	 * All rules should add themselves to the rules filter by default.
	 * Might be overwritten, if a rule does special initialization.
	 *
	 * @return void
	 */
	public function test_init() {
		$this->rule::init();

		self::assertNotFalse(
			has_filter( 'antispam_bee_rules', array( $this->rule, 'add_rule' ) ),
			'add_rule filter was not added'
		);
	}

	/**
	 * Generate a test comment.
	 *
	 * @param int    $id           Comment ID.
	 * @param string $author       Author name.
	 * @param string $author_email Author email.
	 * @param string $author_url   Author URL.
	 * @param string $author_ip    Author IP address.
	 * @param string $content      Content.
	 *
	 * @return array Comment array.
	 */
	protected static function make_comment(
		int $id = 1,
		string $author = 'Test Author',
		string $author_email = 'test.author@example.com',
		string $author_url = 'www.example.com',
		string $author_ip = '192.0.2.1',
		string $content = 'This is the base test comment.'
	): array {
		return array(
			'reaction_type'        => ContentTypeHelper::COMMENT_TYPE,
			'comment_ID'           => $id,
			'comment_author'       => $author,
			'comment_author_email' => $author_email,
			'comment_author_url'   => $author_url,
			'comment_author_IP'    => $author_ip,
			'comment_content'      => $content,
		);
	}
}
