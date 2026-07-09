<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Rules\EmptyData;
use function Brain\Monkey\Functions\when;

class EmptyDataTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( EmptyData::class, 'asb-empty' );
	}

	public function test_verify_comment() {
		$item = array( 'reaction_type' => ContentTypeHelper::COMMENT_TYPE );
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result for empty comment' );

		$item['comment_content'] = 'This is a test.';
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result for empty author IP' );

		$item['comment_author_IP'] = '192.0.2.91';
		when( 'get_option' )->justReturn( false );
		self::assertSame( 0, EmptyData::verify( $item ), 'Unexpected result with no name required' );

		when( 'get_option' )->justReturn( true );
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result without name and mail' );

		$item['comment_author_email'] = 'comments@example.com';
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result without author name' );

		$item['comment_author'] = 'A. Tester';
		self::assertSame( 0, EmptyData::verify( $item ), 'Unexpected result with name and mail' );
	}

	public function test_verify_linkback() {
		$item = array( 'reaction_type' => ContentTypeHelper::LINKBACK_TYPE );
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result for empty comment' );

		$item['comment_content'] = 'This is a test.';
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result for empty author IP' );

		$item['comment_author_IP'] = '192.0.2.91';
		self::assertSame( 999, EmptyData::verify( $item ), 'Unexpected result with empty URL' );

		$item['comment_author_url'] = 'https://linkback.example.com/';
		self::assertSame( 0, EmptyData::verify( $item ), 'Unexpected result with non-empty URL' );
	}
}
