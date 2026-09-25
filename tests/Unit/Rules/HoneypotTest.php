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
		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->allows( 'get_secret_name_for_post' )->andReturns( 'd7dcf95a06' );
		$honeypot_helper->allows( 'get_marker_name_for_post' )->andReturns( 'm4rk3rf13' );

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
		 * Without the marker the form never carried the honeypot, so the rule must
		 * stay silent instead of returning a final, unappealable spam verdict.
		 */
		Honeypot::precheck();
		self::assertArrayNotHasKey(
			'ab_spam__invalid_request',
			$_POST,
			'a form without the honeypot marker must not be treated as an invalid request'
		);

		// Marker present but the secret field stripped: that is a real bot signal.
		$_POST = [
			'm4rk3rf13' => '1',
			'comment'   => 'H1dd3n',
		];
		Honeypot::precheck();
		self::assertSame(
			1,
			$_POST['ab_spam__invalid_request'],
			'a stripped secret field on an injected form should be detected'
		);

		$_POST = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'S3cr3t',
			'comment'    => 'H1dd3n',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'Non-empty hidden field not detected' );

		$_POST = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'S3cr3t',
			'comment'    => '',
		];
		Honeypot::precheck();
		self::assertSame(
			[ 'comment' => 'S3cr3t' ],
			$_POST,
			'Secret was not moved to hidden field, or the marker was left behind'
		);

		// A form filler that writes 0 into every field it does not recognise has
		// still filled the decoy in, even though `empty( '0' )` is true.
		$_POST = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'S3cr3t',
			'comment'    => '0',
		];
		Honeypot::precheck();
		self::assertSame(
			1,
			$_POST['ab_spam__hidden_field'],
			'a honeypot containing the literal string "0" must count as filled in'
		);

		// Honeypot field entirely absent while the secret field is present.
		$_POST = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'S3cr3t',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'Missing hidden field not detected' );

	}
}
