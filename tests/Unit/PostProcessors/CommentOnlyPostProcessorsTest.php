<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\SaveReason;
use AntispamBee\PostProcessors\SendEmail;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * `SaveReason` and `SendEmail` defer their work to the `comment_post` action, which
 * only fires for items that are stored as comments. For anything else the callback
 * would never run — and worse, stay attached, so an unrelated comment inserted later
 * in the same request would be stamped with this item’s data.
 */
class CommentOnlyPostProcessorsTest extends TestCase {

	/**
	 * @return array<string, array{0: class-string, 1: string}>
	 */
	public function comment_only_post_processors(): array {
		return [
			'save reason' => [ SaveReason::class, 'asb-save-reason' ],
			'send email'  => [ SendEmail::class, 'asb-send-email' ],
		];
	}

	/**
	 * @dataProvider comment_only_post_processors
	 *
	 * @param class-string $post_processor The post-processor under test.
	 * @param string       $slug           Its slug.
	 */
	public function test_does_nothing_for_an_item_that_is_not_a_comment( string $post_processor, string $slug ): void {
		$item = [
			'reaction_type' => 'my_plugin_form',
			'body'          => 'Spam',
			'asb_reasons'   => [ 'asb-bbcode' ],
		];

		$processed = $post_processor::process( $item );

		self::assertFalse(
			has_action( 'comment_post' ),
			'no comment_post callback should be attached for a non-comment item'
		);
		self::assertSame(
			[ $slug ],
			$processed['asb_post_processors_failed'],
			'the post-processor should report that it could not process the item'
		);
	}

	/**
	 * @dataProvider comment_only_post_processors
	 *
	 * @param class-string $post_processor The post-processor under test.
	 */
	public function test_still_processes_a_comment( string $post_processor ): void {
		$item = [
			'reaction_type'    => 'comment',
			'comment_post_ID'  => 12,
			'comment_content'  => 'Spam',
			'asb_reasons'      => [ 'asb-bbcode' ],
		];

		$processed = $post_processor::process( $item );

		self::assertTrue(
			has_action( 'comment_post' ),
			'a comment_post callback should be attached for a comment'
		);
		self::assertArrayNotHasKey(
			'asb_post_processors_failed',
			$processed,
			'a comment should be processed without failure'
		);
	}
}
