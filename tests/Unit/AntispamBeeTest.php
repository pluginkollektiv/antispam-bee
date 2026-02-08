<?php

namespace AntispamBee\Tests\Unit\Core;

use Antispam_Bee as Testee;
use AntispamBee\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Filters;

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
		Functions::when( 'wp_parse_url' )->alias('parse_url');
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

		$_SERVER['REMOTE_ADDR']          = '192.0.2.1';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.2, 10.0.0.10';
		$_SERVER['HTTP_X_REAL_IP']       = 'bogus';
		$_SERVER['SCRIPT_NAME']          = '/wp-comments-post.php';
		$_POST['comment']                = $comment;

		$result = Testee::handle_incoming_request( $comment );
		$this->assertSame( '192.0.2.1', $result['comment_author_IP'], 'Unexpected IP with default detection' );

		Filters::expectApplied( 'antispam_bee_trusted_ip' )
				->once()
				->with( '192.0.2.1' )
				->andReturn( '192.0.2.2' );

        $result = Testee::handle_incoming_request( $comment );
        $this->assertSame( '192.0.2.2', $result['comment_author_IP'], 'Unexpected IP with custom hook' );
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

		$_SERVER['REMOTE_ADDR'] = '12.23.34.45';
		$_SERVER['SCRIPT_NAME'] = '/wp-comments-post.php';
		$_POST['comment']       = $comment;

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

	public function test_prepare_comment_field() {
        Functions::when( 'esc_js' )->returnArg();
        Functions::when( 'esc_attr' )->returnArg();

        // Empty data.
        self::assertSame( '', Testee::prepare_comment_field( '' ) );

        // Non-matching textarea.
        $raw = '<p>Text before</p>' .
               '<textarea id="my-textarea" name="text" class="some-class">My Content</textarea>' .
               '<p>Text after</p>';
        self::assertSame( $raw, Testee::prepare_comment_field( $raw ) );

        // Matching textarea.
        $raw = '<p>Text before</p>' .
               '<textarea id="my-textarea" name="comment" class="some-class">My Content</textarea>' .
               '<p>Text after</p>';
        $expected_regex = '#^<p>Text before</p>' .
                          '<textarea autocomplete="new-password"  id="[a-f0-9]{10}"  name="[a-f0-9]{10}"   class="some-class">My Content</textarea>' .
                          '<textarea id="comment" aria-label="hp-comment" aria-hidden="true" name="comment" autocomplete="new-password" style=".+" tabindex="-1"></textarea>' .
                          '<script data-noptimize>.+document.getElementById\("[a-f0-9]{10}"\).setAttribute\( "id", "comment" \);</script>' .
                          '<p>Text after</p>$#';
        self::assertRegExp( $expected_regex, Testee::prepare_comment_field( $raw ) );

        // Unquoted name.
        $raw = '<p>Text before</p>' .
               '<textarea id="my-textarea" name=comment class="some-class">My Content</textarea>' .
               '<p>Text after</p>';
        self::assertRegExp( $expected_regex, Testee::prepare_comment_field( $raw ) );
	}

	/**
	 * Provide test data to test_spam_reasons method.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public function spam_reasons_data_provider() {
		return array(
			array(
				// Detect spam word regex pattern in author.
				array(
					'comment_author' => 'Buy Viagra',
				),
				'regexp',
			),
			array(
				// Detect BBCode spam in content.
				array(
					'comment_content' => "Test BB Spam\n[url=www.google.com]Link[/url]",
				),
				'bbcode',
			),
			// Detect spam word regex pattern combination content + mail.
			// Attention, the order of the provided data here is important -.-, needs a rework of the codebase
			// @ToDo: static $_reason
			array(
				array(
					'comment_content' => "this is a pharmacy, why does it work now?.",
					'comment_author_email' => 'test@yandex.ru',
				),
				'regexp',
			),
		);
	}

	/**
	 * Get the base comment that is used as standard reference.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	private function get_base_comment() {
		return array(
			'comment_ID'           => 1,
			'comment_author'       => 'Test Author',
			'comment_author_email' => 'test.author@mail.server',
			'comment_author_url'   => 'www.testdomain.com',
			'comment_author_IP'    => '128.0.0.1',
			'comment_content'      => 'This is the base test comment.',
		);
	}
}
