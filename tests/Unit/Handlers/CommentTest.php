<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\Reaction;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

use function Brain\Monkey\Functions\stubs;

/**
 * Unit tests for {@see Comment}.
 */
class CommentTest extends TestCase {

	public function test_process() {
		global $_POST;
		global $_SERVER;

		$_POST   = null;
		$_SERVER = [
			'HTTP_CLIENT_IP' => '192.0.2.100',
			'SCRIPT_NAME'    => '/index.php'
		];

		stubs(
			[
				'esc_url_raw'  => function (string $url) {
					return $url;
				},
				'wp_parse_url' => 'parse_url',
				'wp_unslash'   => function ($value) {
					return $value;
				},
			]
		);

		$processed = [];
		mock('overload:' . Reaction::class )
			->expects( 'process' )
			->withArgs( function( $input ) use ( &$processed ) {
				$processed[] = $input;
				return true;
			} );

		$comment = [ 'comment_type' => 'comment' ];

		$result = Comment::process( $comment );
		self::assertSame( '192.0.2.100', $result['comment_author_IP'], 'Unexpected author IP on index.php' );
		self::assertEmpty( $processed, 'Comment should no have been processed on index.php' );

		$_SERVER['SCRIPT_NAME'] = '';
		$result = Comment::process( $comment );
		self::assertSame( '192.0.2.100', $result['comment_author_IP'], 'Unexpected author IP on invalid request' );
		self::assertSame( 1, $result['ab_spam__invalid_request'], 'Invalid request not detected' );
		self::assertEmpty( $processed, 'Comment should no have been processed on invalid request' );

		$_SERVER['SCRIPT_NAME'] = '/wp-comments-post.php';
		$result = Comment::process( $comment );
		self::assertSame( '192.0.2.100', $result['comment_author_IP'], 'Unexpected author IP on invalid request' );
		self::assertArrayNotHasKey( 'processed', $result, 'Comment should no have been processed without POST data' );

		$_POST = 'test me';
		$result = Comment::process( $comment );
		self::assertSame( [ $result ], $processed, 'Comment was not processed' );

		$comment = [ 'comment_type' => 'linkback' ];
		$result = Comment::process( $comment );
		self::assertSame( $comment, $result, 'Linkback should not be modified by comment handler' );
	}
}
