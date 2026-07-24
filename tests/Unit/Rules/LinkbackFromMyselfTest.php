<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Rules\LinkbackFromMyself;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see LinkbackFromMyself}.
 */
class LinkbackFromMyselfTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( LinkbackFromMyself::class, 'asb-linkback-from-myself' );
	}

	/**
	 * Without a source URL or a target post ID there is nothing to check.
	 */
	public function test_verify_returns_zero_without_url_or_post_id() {
		self::assertSame( 0, LinkbackFromMyself::verify( [] ), 'unexpected result for empty item' );
		self::assertSame(
			0,
			LinkbackFromMyself::verify( [ 'url' => 'https://example.com/foo/' ] ),
			'unexpected result without target post ID'
		);
		self::assertSame(
			0,
			LinkbackFromMyself::verify( [ 'post_id' => 1 ] ),
			'unexpected result without source URL'
		);
	}

	/**
	 * A linkback whose source is not on this site is never "from myself".
	 */
	public function test_verify_returns_zero_for_external_linkback() {
		when( 'home_url' )->justReturn( 'https://example.com' );

		$item = [
			'reaction_type' => ContentTypeHelper::LINKBACK_TYPE,
			'url'           => 'https://external.example.net/post/',
			'post_id'       => 42,
		];
		self::assertSame( 0, LinkbackFromMyself::verify( $item ), 'external linkback should not match' );
	}

	/**
	 * A linkback whose source is one of our own posts and that links back to the
	 * target post is a linkback from ourselves and must be whitelisted (-100).
	 */
	public function test_verify_detects_linkback_from_myself() {
		when( 'home_url' )->justReturn( 'https://example.com' );
		when( 'url_to_postid' )->justReturn( 7 );
		when( 'get_post' )->justReturn( (object) [ 'post_content' => 'See https://example.com/target/' ] );
		when( 'get_permalink' )->justReturn( 'https://example.com/target/' );
		when( 'wp_extract_urls' )->justReturn( [ 'https://example.com/target/' ] );

		$item = [
			'reaction_type' => ContentTypeHelper::LINKBACK_TYPE,
			'url'           => 'https://example.com/source/',
			'post_id'       => 42,
		];
		self::assertSame( -100, LinkbackFromMyself::verify( $item ), 'self linkback should be whitelisted' );
	}
}
