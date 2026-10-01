<?php

namespace AntispamBee\Tests\Unit\PostProcessors;

use AntispamBee\PostProcessors\SaveReason;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\expect;

/**
 * Unit tests for {@see SaveReason}.
 *
 * The reasons are persisted on `wp_insert_comment` rather than on `comment_post`,
 * because `comment_post` is fired by `wp_new_comment()` only and would miss every
 * comment the REST API inserts.
 */
class SaveReasonTest extends TestCase {

	/**
	 * The callback is hooked to the action that fires for every insertion channel.
	 */
	public function test_process_hooks_wp_insert_comment() {
		SaveReason::process(
			[
				'asb_reasons' => [ 'asb-honeypot' ],
			]
		);

		self::assertNotFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'The reasons should be saved when the comment is inserted'
		);
	}

	/**
	 * An item that is going to be deleted anyway gets no meta and no hook.
	 */
	public function test_process_skips_items_marked_for_deletion() {
		SaveReason::process(
			[
				'asb_reasons'          => [ 'asb-honeypot' ],
				'asb_marked_as_delete' => true,
			]
		);

		self::assertFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'Nothing should be saved for an item that is deleted'
		);
	}

	/**
	 * The reasons are written once, and the callback unhooks itself so a second
	 * comment inserted in the same request cannot inherit them.
	 */
	public function test_save_reasons_writes_once_and_unhooks() {
		expect( 'add_comment_meta' )
			->once()
			->with( 42, 'antispam_bee_reason', 'asb-honeypot,asb-regexp' );

		SaveReason::process(
			[
				'asb_reasons' => [ 'asb-honeypot', 'asb-regexp' ],
			]
		);

		SaveReason::save_reasons( 42 );

		self::assertFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'The callback should have unhooked itself after saving'
		);
	}

	/**
	 * Without reasons the post-processor reports itself as failed and saves nothing.
	 */
	public function test_process_without_reasons_is_recorded_as_failed() {
		$item = SaveReason::process( [] );

		self::assertContains(
			SaveReason::get_slug(),
			$item['asb_post_processors_failed'],
			'A missing reason list should be recorded as a failure'
		);
		self::assertFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'Nothing should be hooked without reasons'
		);
	}
}
