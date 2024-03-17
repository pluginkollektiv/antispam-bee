<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\Honeypot;

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
}
