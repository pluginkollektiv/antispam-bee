<?php

namespace AntispamBee\Tests\Unit\Handlers;

use AntispamBee\Handlers\Comment;
use AntispamBee\PostProcessors\Base as PostProcessorBase;
use AntispamBee\Rules\Base as RuleBase;
use WP_Error;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

if ( ! defined( 'AntispamBee\PLUGIN_PATH' ) ) {
	define( 'AntispamBee\PLUGIN_PATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

/**
 * Test rule with a configurable score.
 */
class CommentRestTestRule extends RuleBase {
	protected static $slug = 'test-rest-rule';

	public static $score = 0;

	public static function verify( array $item ): int {
		return self::$score;
	}

	public static function get_name(): string {
		return 'REST test rule';
	}
}

/**
 * Test post-processor that records the item it was handed.
 */
class CommentRestTestPostProcessor extends PostProcessorBase {
	protected static $slug = 'test-rest-post-processor';

	public static $items = [];

	public static function process( array $item ): array {
		self::$items[] = $item;

		return $item;
	}

	public static function get_name(): string {
		return 'REST test post-processor';
	}

	public static function get_label(): ?string {
		return null;
	}

	public static function get_description(): ?string {
		return null;
	}
}

/**
 * Test post-processor that marks the item for deletion, like {@see \AntispamBee\PostProcessors\Delete}.
 */
class CommentRestDeletingPostProcessor extends PostProcessorBase {
	protected static $slug            = 'test-rest-deleting-post-processor';
	protected static $marks_as_delete = true;

	public static function process( array $item ): array {
		$item['asb_marked_as_delete'] = true;

		return $item;
	}

	public static function get_name(): string {
		return 'REST test deleting post-processor';
	}

	public static function get_label(): ?string {
		return null;
	}

	public static function get_description(): ?string {
		return null;
	}
}

/**
 * Unit tests for {@see Comment::process_rest()}.
 *
 * The REST controller inserts a comment with `wp_insert_comment()` and never fires
 * `preprocess_comment`, so this path has its own entry point and its own way of
 * recording a spam verdict.
 */
class CommentRestTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		CommentRestTestRule::$score         = 0;
		CommentRestTestPostProcessor::$items = [];

		$_SERVER = [ 'REMOTE_ADDR' => '192.0.2.100' ];

		stubs(
			[
				'esc_url_raw'   => function ( string $url ) {
					return $url;
				},
				'wp_parse_url'  => 'parse_url',
				'wp_unslash'    => function ( $value ) {
					return $value;
				},
				'wp_installing' => false,
				'is_admin'      => false,
			]
		);

		when( 'current_user_can' )->justReturn( false );
	}

	/**
	 * Register the test rule, and the given post-processors.
	 *
	 * @param array<class-string> $post_processors Post-processors to register.
	 */
	private function register_components( array $post_processors = [] ): void {
		expectApplied( 'antispam_bee_rules' )->andReturn( [ CommentRestTestRule::class ] );
		expectApplied( 'antispam_bee_post_processors' )->andReturn( $post_processors );
	}

	/**
	 * A prepared comment the rules consider ham is handed back untouched.
	 */
	public function test_ham_is_returned_unchanged() {
		$this->register_components();
		CommentRestTestRule::$score = 0;

		$prepared = [
			'comment_type'    => 'comment',
			'comment_content' => 'A perfectly ordinary comment.',
			'comment_approved' => 1,
		];

		$result = Comment::process_rest( $prepared );

		self::assertIsArray( $result, 'A ham comment should stay an array' );
		self::assertSame( 1, $result['comment_approved'], 'A ham comment must keep its approval status' );
	}

	/**
	 * Spam is marked on the prepared comment itself, because the REST controller has
	 * already asked `wp_allow_comment()` before this filter runs.
	 */
	public function test_spam_is_marked_on_the_prepared_comment() {
		$this->register_components( [ CommentRestTestPostProcessor::class ] );
		CommentRestTestRule::$score = 999;

		$prepared = [
			'comment_type'     => 'comment',
			'comment_content'  => 'Buy cheap pills.',
			'comment_approved' => 1,
		];

		$result = Comment::process_rest( $prepared );

		self::assertIsArray( $result, 'A spam comment should still be an array' );
		self::assertSame( 'spam', $result['comment_approved'], 'A spam comment must be marked as spam' );
		self::assertCount( 1, CommentRestTestPostProcessor::$items, 'The post-processors should have run' );
		self::assertSame(
			[ 'test-rest-rule' ],
			CommentRestTestPostProcessor::$items[0]['asb_reasons'],
			'The spam reason should have been handed to the post-processors'
		);
	}

	/**
	 * A comment a post-processor marked for deletion is refused with a WP_Error, so
	 * that core can turn it into a REST response instead of the `die()` the form
	 * path uses.
	 */
	public function test_deleted_spam_is_refused_with_an_error() {
		$this->register_components( [ CommentRestDeletingPostProcessor::class ] );
		CommentRestTestRule::$score = 999;

		$result = Comment::process_rest(
			[
				'comment_type'    => 'comment',
				'comment_content' => 'Buy cheap pills.',
			]
		);

		self::assertInstanceOf( WP_Error::class, $result, 'Deleted spam should be refused' );
		self::assertSame( 'rest_comment_spam', $result->get_error_code(), 'Unexpected error code' );
		self::assertSame( [ 'status' => 403 ], $result->get_error_data(), 'Deleted spam should answer with a 403' );
	}

	/**
	 * The client IP fills an absent author IP on this channel too.
	 */
	public function test_client_ip_is_filled_in() {
		$this->register_components();

		$result = Comment::process_rest( [ 'comment_type' => 'comment' ] );

		self::assertSame( '192.0.2.100', $result['comment_author_IP'], 'The client IP should fill an empty author IP' );
	}

	/**
	 * An error handed in by an earlier filter is passed straight through.
	 */
	public function test_existing_error_is_passed_through() {
		$error = new WP_Error( 'rest_comment_invalid', 'Nope.' );

		self::assertSame( $error, Comment::process_rest( $error ), 'An existing error must be returned untouched' );
	}

	/**
	 * A reaction of another type is not a comment and is left alone.
	 */
	public function test_other_reaction_types_are_left_alone() {
		$linkback = [ 'comment_type' => 'trackback', 'comment_content' => 'Buy cheap pills.' ];

		self::assertSame( $linkback, Comment::process_rest( $linkback ), 'A trackback must not be handled here' );
	}

	/**
	 * A moderator working in the admin is not a public submission on this channel either.
	 */
	public function test_trusted_context_is_not_verified() {
		when( 'is_admin' )->justReturn( true );
		when( 'current_user_can' )->justReturn( true );

		$prepared = [ 'comment_type' => 'comment', 'comment_content' => 'Buy cheap pills.' ];

		self::assertSame( $prepared, Comment::process_rest( $prepared ), 'A moderator submission must not be verified' );
	}
}
