<?php

namespace AntispamBee\Tests\Unit\Core;

use Antispam_Bee as Testee;
use AntispamBee\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test case for the factory class.
 *
 * @since   2.7.0
 */
class FactoryTest extends TestCase {

	/**
	 * Set up the test environment.
	 *
	 * @since 2.7.0
	 */
	protected function setUp() {
		parent::setUp();

		Functions::when( 'get_bloginfo' )->justReturn( 'https://domain.com/' );
		Functions::when( 'is_admin' )->justReturn( false );
		Functions::expect( 'wp_unslash' )
			->andReturnUsing(
				function( $data ) {
					return $data;
				}
			);

		Functions::when( 'get_option' )->justReturn( $this->get_options() );

		Testee::init();
	}

	/**
	 * Get a modified set of default options.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	private function get_options() {
		$defaults = Testee::$defaults['options'];

		$defaults['no_notice']         = 0;
		$defaults['spam_ip']           = 0;
		$defaults['already_commented'] = 0;
		$defaults['gravatar_check']    = 0;

		return $defaults;
	}

	/**
	 * Tests whether the IP address is fetched.
	 *
	 * @since  2.7.0
	 *
	 * @covers Testee::handle_incoming_request()
	 */
	public function test_gets_ip_address() {
		$comment = $this->get_base_comment();

		$_SERVER['HTTP_CLIENT_IP'] = '12.23.34.45';
		$_SERVER['REQUEST_URI']    = 'https://domain.com/wp-comments-post.php';
		$_POST['comment']          = $comment;

		$result = Testee::handle_incoming_request( $comment );
		$this->assertSame( '12.23.34.45', $result['comment_author_IP'] );
	}

	/**
	 * Tests various spam reasons.
	 *
	 * @since        2.7.0
	 *
	 * @dataProvider spam_reasons_data_provider
	 *
	 * @covers       Testee::handle_incoming_request()
	 *
	 * @param array  $comment Comment overrides to use.
	 * @param string $reason  Expected spam reason to catch.
	 */
	public function test_spam_reasons( $comment, $reason ) {
		$comment = array_merge( $this->get_base_comment(), $comment );

		$_SERVER['HTTP_CLIENT_IP'] = '12.23.34.45';
		$_SERVER['REQUEST_URI']    = 'https://domain.com/wp-comments-post.php';
		$_POST['comment']          = $comment;

		// This is where we check for the spam reason that was detected.
		Functions::expect( 'add_comment_meta' )->once()
		         ->with(
			         1,
			         'antispam_bee_reason',
			         $reason
		         );

		// We need to explicitly trigger the handling and...
		Testee::handle_incoming_request( $comment );
		// ... let Antispam Bee add the spam reason as comment meta.
		Testee::add_spam_reason_to_comment( 1 );
	}

	/**
	 * Provide test data to test_spam_reasons method.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public function spam_reasons_data_provider() {
		return [
			[
				// Detect spam word regex pattern in author.
				[
					'comment_author' => 'Buy Viagra',
				],
				'regexp',
			],
			[
				// Detect BBCode spam in content.
				[
					'comment_content' => "Test BB Spam\n[url=www.google.com]Link[/url]",
				],
				'bbcode',
			],
			// Detect spam word regex pattern combination content + mail.
			// Attention, the order of the provided data here is important -.-, needs a rework of the codebase
			// @ToDo: static $_reason
			[
				[
					'comment_content' => "this is a pharmacy, why does it work now?.",
					'comment_author_email' => 'test@yandex.ru',
				],
				'regexp',
			],
		];
	}

	/**
	 * Get the base comment that is used as standard reference.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	private function get_base_comment() {
		return [
			'comment_ID'           => 1,
			'comment_author'       => 'Test Author',
			'comment_author_email' => 'test.author@mail.server',
			'comment_author_url'   => 'www.testdomain.com',
			'comment_author_IP'    => '128.0.0.1',
			'comment_content'      => 'This is the base test comment.',
		];
	}
}
