<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\BBCode;

class BBCodeTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( BBCode::class, 'asb-bbcode' );
	}

	public function test_verify() {
		$item = [];
		self::assertSame( 0, BBCode::verify( $item ), 'Unexpected result for empty item' );

		$item['body'] = 'No link here.';
		self::assertSame( 0, BBCode::verify( $item ), 'Unexpected result for comment without BBCode' );

		$item['body'] = 'Link to [url]https://example.com[/url].';
		self::assertSame( 1, BBCode::verify( $item ), 'Unexpected result for comment with simple URL' );

		$item['body'] = 'This is a [url=https://example.com]link[/url].';
		self::assertSame( 1, BBCode::verify( $item ), 'Unexpected result for comment with wrapped URL' );

		$item['body'] = 'This is a [UrL=https://example.com]link[/uRl].';
		self::assertSame( 1, BBCode::verify( $item ), 'Check should be case-insensitive' );

		$item['body'] = 'This is [b]bold[/b] and [i]italic[/i] text.';
		self::assertSame( 0, BBCode::verify( $item ), 'Unexpected result for comment with other BBCodes' );

		$item['author'] = 'Unknown [url=https://example.com]field[/url].';
		self::assertSame( 1, BBCode::verify( $item ), 'Unexpected result for BBCode in different fields' );
	}
}
