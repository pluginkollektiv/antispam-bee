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

		$_POST = [];
		self::assertSame( 0, Honeypot::verify( $item ), 'Comment without HP field should be OK' );

		$_POST['ab_spam__hidden_field'] = 1;
		self::assertSame( 999, Honeypot::verify( $item ), 'Comment with HP 1 should trigger the rule' );
	}

	public function test_init() {
		parent::test_init();

		self::assertNotFalse(
			has_filter( 'comment_form_field_comment' ),
			'The comment_form_field_comment filter was not added'
		);
	}

	public function test_precheck() {
		global $_POST;
		global $_SERVER;

		stubs(
			[
				'esc_url_raw'  => function ( string $url ) {
					return $url;
				},
				'is_feed'      => false,
				'is_trackback' => false,
				'wp_parse_url' => 'parse_url',
				'wp_unslash'   => function ( $value ) {
					return $value;
				},
			]
		);
		$injection_failed = false;

		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->allows( 'get_secret_name_for_post' )->andReturns( 'd7dcf95a06' );
		$honeypot_helper->allows( 'injection_failed' )->andReturnUsing(
			function () use ( &$injection_failed ) {
				return $injection_failed;
			}
		);

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

		/*
		 * A bot posting the fields core expects straight to wp-comments-post.php,
		 * without ever using the rendered form. This is what the honeypot is the
		 * gate for, so it has to be caught.
		 */
		$_POST = [
			'comment'         => 'Your point of view caught my eye.',
			'author'          => 'Bot',
			'email'           => 'bot@example.com',
			'comment_post_ID' => '433',
		];
		Honeypot::precheck();
		self::assertSame(
			1,
			$_POST['ab_spam__invalid_request'],
			'a submission without the secret field should be treated as an invalid request'
		);

		$_POST = [
			'd7dcf95a06' => 'S3cr3t',
			'comment'    => 'H1dd3n',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'Non-empty hidden field not detected' );

		$_POST = [
			'd7dcf95a06' => 'S3cr3t',
			'comment'    => '',
		];
		Honeypot::precheck();
		self::assertSame( [ 'comment' => 'S3cr3t' ], $_POST, 'Secret was not moved to hidden field' );

		// Honeypot field entirely absent while the secret field is present.
		$_POST = [
			'd7dcf95a06' => 'S3cr3t',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'Missing hidden field not detected' );

		/*
		 * The server could not place the honeypot into the form it rendered, so a
		 * genuine comment arrives without the secret field and must not be judged.
		 */
		$injection_failed = true;

		$_POST = [
			'comment' => 'A genuine comment.',
		];
		Honeypot::precheck();
		self::assertSame(
			[ 'comment' => 'A genuine comment.' ],
			$_POST,
			'a form the honeypot could not be injected into must not be judged by it'
		);
	}
}
