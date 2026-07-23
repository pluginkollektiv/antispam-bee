<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\Linkback;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Linkback}.
 */
class LinkbackTest extends TestCase {

	/**
	 * Invoke the protected static build_payload().
	 *
	 * @param array $reaction Raw reaction data.
	 * @return array Normalized payload.
	 */
	private static function build_payload( array $reaction ): array {
		when( 'wp_parse_url' )->alias( 'parse_url' ); // used for the 'host' attribute
		$method = new \ReflectionMethod( Linkback::class, 'build_payload' );
		$method->setAccessible( true );

		return $method->invoke( null, $reaction );
	}

	/**
	 * The linkback's blog name lives in comment_author; the normalized payload
	 * must carry it so LinkbackPostTitleIsBlogName can compare it to the title.
	 */
	public function test_payload_maps_author_from_comment_author() {
		$payload = self::build_payload(
			[
				'comment_author'     => 'Example Blog',
				'comment_content'    => 'Some excerpt.',
				'comment_author_url' => 'https://example.com/post/',
				'comment_author_IP'  => '192.0.2.10',
				'comment_post_ID'    => 1,
			]
		);

		self::assertSame( 'linkback', $payload['reaction_type'] );
		self::assertSame( 'Example Blog', $payload['author'], 'author must be mapped from comment_author' );
		self::assertSame( 'Some excerpt.', $payload['body'] );
		self::assertSame( 'https://example.com/post/', $payload['url'] );
		self::assertSame( 'example.com', $payload['host'] );
	}

	/**
	 * Linkback fields can arrive as arrays; they must be normalized to scalars.
	 */
	public function test_payload_normalizes_array_valued_author() {
		$payload = self::build_payload(
			[
				'comment_author'     => [ 'Example Blog', 'ignored' ],
				'comment_content'    => 'x',
				'comment_author_url' => '',
				'comment_author_IP'  => '192.0.2.10',
			]
		);

		self::assertSame( 'Example Blog', $payload['author'], 'array author must be reduced to its first element' );
	}

	/**
	 * A missing author yields an empty string (not a warning / null).
	 */
	public function test_payload_author_defaults_to_empty() {
		$payload = self::build_payload(
			[
				'comment_content'    => 'x',
				'comment_author_url' => '',
				'comment_author_IP'  => '192.0.2.10',
			]
		);

		self::assertSame( '', $payload['author'] );
	}
}
