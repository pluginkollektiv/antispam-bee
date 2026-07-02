<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\Honeypot;

use function Brain\Monkey\Functions\stubs;

/**
 * Unit tests for {@see Honeypot}.
 *
 * @backupGlobals enabled
 */
class HoneypotTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( Honeypot::class, 'asb-honeypot' );
	}

	public function test_verify() {
		global $_POST;

		$item = self::make_comment();

		$_POST = array();
		self::assertSame( 0, Honeypot::verify( $item ), 'comment without HP field should be OK' );

		$_POST['ab_spam__hidden_field'] = 1;
		self::assertSame( 999, Honeypot::verify( $item ), 'comment with HP 1 should trigger the rule' );
	}

	public function test_init() {
		parent::test_init();

		self::assertNotFalse(
			has_filter( 'comment_form_field_comment' ),
			'comment_form_field_comment filter was not added'
		);
	}

	public function test_precheck() {
		global $_POST;
		global $_SERVER;

		stubs(
			[
				'esc_url_raw' => function ( string $url ) {
					return $url;
				},
				'is_feed' => false,
				'is_trackback' => false,
				'wp_parse_url' => 'parse_url',
				'wp_unslash' => function ( $value ) {
					return $value;
				},
			]
		);
		mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class )
			->expects( 'get_secret_name_for_post' )
			->andReturns( 'd7dcf95a06' );

		$_POST = [];

		// Send the following requests to the wrong URL.
		$_SERVER = [ 'SCRIPT_NAME' => '/index.php' ];

		Honeypot::precheck();
		self::assertEmpty( $_POST, 'Empty POST data modified unexpectedly' );

		$_POST = [ 'foo' => 'bar' ];
		Honeypot::precheck();
		self::assertSame( [ 'foo' => 'bar' ], $_POST, 'POST data modified on index.php' );

		// Send all following requests to the correct URL.
		$_SERVER = [ 'SCRIPT_NAME' => '/wp-comments-post.php' ];

		$_POST = [
			'd7dcf95a06' => 'S3cr3t',
			'comment' => 'H1dd3n',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'non-empty hidden fiend not detected' );

		$_POST = [
			'd7dcf95a06' => 'S3cr3t',
			'comment' => '',
		];
		Honeypot::precheck();
		self::assertSame(
			[ 'comment' => 'S3cr3t' ],
			$_POST,
			'secret was not moved to hidden field'
		);

	}
}
