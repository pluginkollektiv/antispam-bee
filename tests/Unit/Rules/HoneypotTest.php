<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\Honeypot;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Honeypot}.
 *
 * @backupGlobals enabled
 */
class HoneypotTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( Honeypot::class, 'asb-honeypot' );
	}

	/**
	 * Stub the stored settings so the rule can read its own active flag.
	 *
	 * Reading the settings runs the update check first, so report the database as
	 * current to keep the v2 migration out of these tests, the same way
	 * {@see \AntispamBee\Tests\Unit\Crons\DeleteSpamCronTest} does.
	 *
	 * @param string|null $active Value of the active flag, or null to leave it unset.
	 *
	 * @return void
	 */
	private function stub_honeypot_setting( ?string $active ): void {
		$comment = null === $active ? [] : [ 'rule_asb_honeypot_active' => $active ];

		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0-beta.3' ] );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) use ( $comment ) {
				if ( 'antispambee_db_version' === $name ) {
					return '3.0.0-beta.3';
				}

				if ( \AntispamBee\Helpers\Settings::OPTION_NAME === $name ) {
					return [ 'comment' => $comment ];
				}

				return $default;
			}
		);
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
		$this->stub_honeypot_setting( 'on' );
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

		// Honeypot field entirely absent while the secret field is present.
		$_POST = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'S3cr3t',
		];
		Honeypot::precheck();
		self::assertSame( 1, $_POST['ab_spam__hidden_field'], 'Missing hidden field not detected' );

	}

	/**
	 * A form rendered before the marker existed, or by any path that injected the
	 * secret field without one, still has the visitor's text under the secret name
	 * and an empty `comment`. Repairing that is independent of judging it: if the
	 * rule bails out before the swap is reversed, the comment is submitted empty.
	 */
	public function test_secret_field_is_restored_even_without_a_marker() {
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

		$_SERVER = [ 'SCRIPT_NAME' => '/wp-comments-post.php' ];
		$_POST   = [
			'd7dcf95a06' => 'A real comment from a cached form',
			'comment'    => '',
		];

		Honeypot::precheck();

		self::assertSame(
			'A real comment from a cached form',
			$_POST['comment'],
			'the visitor text must be restored even when the form carries no marker'
		);
		self::assertArrayNotHasKey(
			'd7dcf95a06',
			$_POST,
			'the secret field should be consumed once it has been restored'
		);
		self::assertArrayNotHasKey(
			'ab_spam__invalid_request',
			$_POST,
			'a form without a marker must not be judged'
		);
	}

	/**
	 * Turning the honeypot off must not strand forms it already rewrote. The
	 * un-swap is repair, not judgement, so it has to happen even when the rule
	 * casts no verdict.
	 */
	public function test_secret_field_is_restored_when_the_rule_is_inactive() {
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
		// The checkbox is unchecked, so the key is absent entirely.
		$this->stub_honeypot_setting( null );
		$honeypot_helper = mock( 'overload:' . \AntispamBee\Helpers\Honeypot::class );
		$honeypot_helper->allows( 'get_secret_name_for_post' )->andReturns( 'd7dcf95a06' );
		$honeypot_helper->allows( 'get_marker_name_for_post' )->andReturns( 'm4rk3rf13' );

		$_SERVER = [ 'SCRIPT_NAME' => '/wp-comments-post.php' ];
		$_POST   = [
			'm4rk3rf13'  => '1',
			'd7dcf95a06' => 'Text from a form rendered while the rule was on',
			'comment'    => 'bait',
		];

		Honeypot::precheck();

		self::assertSame(
			'Text from a form rendered while the rule was on',
			$_POST['comment'],
			'an inactive rule must still repair a form it had already rewritten'
		);
		self::assertArrayNotHasKey(
			'ab_spam__hidden_field',
			$_POST,
			'an inactive rule must not cast a verdict'
		);
		self::assertArrayNotHasKey(
			'ab_spam__invalid_request',
			$_POST,
			'an inactive rule must not cast a verdict'
		);
	}
}
