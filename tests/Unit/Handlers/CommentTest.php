<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\Comment;
use AntispamBee\Handlers\Reaction;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see Comment}.
 */
class CommentTest extends TestCase {

	/**
	 * The verification decision is driven by the request context, not by the executing script.
	 *
	 * Mockery's `overload:` may only be applied once per process, so the whole contract is
	 * asserted in a single test method against one overloaded parent.
	 */
	public function test_process() {
		global $_POST;
		global $_SERVER;

		$_POST   = null;
		$_SERVER = [
			'REMOTE_ADDR' => '192.0.2.100',
		];

		$is_admin     = false;
		$can_moderate = false;
		$skip_filter  = null;

		stubs(
			[
				'esc_url_raw'  => function ( string $url ) {
					return $url;
				},
				'wp_parse_url' => 'parse_url',
				'wp_unslash'   => function ( $value ) {
					return $value;
				},
				'wp_installing' => false,
			]
		);

		when( 'is_admin' )->alias(
			function () use ( &$is_admin ) {
				return $is_admin;
			}
		);
		when( 'current_user_can' )->alias(
			function () use ( &$can_moderate ) {
				return $can_moderate;
			}
		);
		when( 'apply_filters' )->alias(
			function ( $hook, $value = null ) use ( &$skip_filter ) {
				if ( 'antispam_bee_skip_comment_verification' === $hook && null !== $skip_filter ) {
					return $skip_filter;
				}

				return $value;
			}
		);

		$processed = [];
		mock( 'overload:' . Reaction::class )
			->allows( 'process' )
			->withArgs( function ( $input ) use ( &$processed ) {
				$processed[] = $input;

				return true;
			} );

		$comment = [ 'comment_type' => 'comment' ];

		// The front-end comment form is verified, as it always was.
		$_SERVER['SCRIPT_NAME'] = '/wp-comments-post.php';
		$_POST                  = [ 'comment' => 'Hello' ];
		$result                 = Comment::process( $comment );
		self::assertSame( '192.0.2.100', $result['comment_author_IP'], 'The client IP fills an empty author IP' );
		self::assertCount( 1, $processed, 'Comment should have been processed on wp-comments-post.php' );

		/*
		 * Every caller of `wp_new_comment()` reaches this handler through
		 * `preprocess_comment`. XML-RPC's `wp.newComment` is served by /xmlrpc.php with
		 * an empty $_POST (the payload is a raw XML body), and used to skip every rule.
		 */
		$processed              = [];
		$_SERVER['SCRIPT_NAME'] = '/xmlrpc.php';
		$_POST                  = [];
		$result                 = Comment::process( $comment );
		self::assertCount( 1, $processed, 'Comment submitted over XML-RPC should have been processed' );

		// A REST or headless front-end submission is verified too.
		$processed              = [];
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$result                 = Comment::process( $comment );
		self::assertCount( 1, $processed, 'Comment submitted outside the comment form should have been processed' );

		// A moderator working in the admin is not a public submission.
		$processed    = [];
		$is_admin     = true;
		$can_moderate = true;
		$result       = Comment::process( $comment );
		self::assertEmpty( $processed, 'Comment created by a moderator in the admin should not have been processed' );

		// A visitor hitting an admin-side endpoint is still verified.
		$processed    = [];
		$can_moderate = false;
		$result       = Comment::process( $comment );
		self::assertCount( 1, $processed, 'Comment from a non-moderator should have been processed' );

		// The decision is filterable.
		$processed   = [];
		$is_admin    = false;
		$skip_filter = true;
		$result      = Comment::process( $comment );
		self::assertEmpty( $processed, 'The filter should have skipped the verification' );
		$skip_filter = null;

		/*
		 * An IP supplied by the caller survives. Core only falls back to REMOTE_ADDR for
		 * an absent value, so importers and plugins passing a historical IP must keep it.
		 */
		$processed = [];
		$result    = Comment::process(
			[
				'comment_type'      => 'comment',
				'comment_author_IP' => '198.51.100.7',
			]
		);
		self::assertSame( '198.51.100.7', $result['comment_author_IP'], 'A supplied IP must not be overwritten' );

		// An unusable REMOTE_ADDR is left alone instead of blanking the field.
		$processed              = [];
		$_SERVER['REMOTE_ADDR'] = 'fe80::1%eth0';
		$result                 = Comment::process(
			[
				'comment_type'      => 'comment',
				'comment_author_IP' => '203.0.113.9',
			]
		);
		self::assertSame( '203.0.113.9', $result['comment_author_IP'], 'A valid IP must not be clobbered with an empty string' );
		$_SERVER['REMOTE_ADDR'] = '192.0.2.100';

		// A reaction of another type is left untouched.
		$linkback = [ 'comment_type' => 'linkback' ];
		$result   = Comment::process( $linkback );
		self::assertSame( $linkback, $result, 'Linkback should not be modified by comment handler' );
	}
}
