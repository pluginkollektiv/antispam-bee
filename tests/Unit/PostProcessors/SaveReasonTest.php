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
				'comment_post_ID' => 1,
				'asb_reasons'     => [ 'asb-honeypot' ],
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
				'comment_post_ID'      => 1,
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
				'comment_post_ID' => 1,
				'asb_reasons'     => [ 'asb-honeypot', 'asb-regexp' ],
			]
		);

		SaveReason::save_reasons( 42, (object) [ 'comment_approved' => 'spam' ] );

		self::assertFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'The callback should have unhooked itself after saving'
		);
	}

	/**
	 * A comment that is not spam is not the one the reasons belong to, so it gets no
	 * meta and the callback keeps waiting for the spam comment.
	 */
	public function test_save_reasons_skips_a_comment_that_is_not_spam() {
		expect( 'add_comment_meta' )->never();

		SaveReason::process(
			[
				'comment_post_ID' => 1,
				'asb_reasons'     => [ 'asb-honeypot' ],
			]
		);

		SaveReason::save_reasons( 41, (object) [ 'comment_approved' => '1' ] );

		self::assertNotFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'The callback should still wait for the spam comment'
		);
	}

	/**
	 * Without reasons the post-processor reports itself as failed and saves nothing.
	 */
	public function test_process_without_reasons_is_recorded_as_failed() {
		$item = SaveReason::process( [ 'comment_post_ID' => 1 ] );

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

	/**
	 * Mirrors the comment_post_ID guard added alongside {@see SendEmail}: the
	 * reason is stored as comment meta, so an item that never becomes a comment
	 * must not leave a hook running either.
	 */
	public function test_process_without_a_comment_id_is_recorded_as_failed() {
		$item = SaveReason::process( [ 'asb_reasons' => [ 'asb-honeypot' ] ] );

		self::assertContains(
			SaveReason::get_slug(),
			$item['asb_post_processors_failed'],
			'A missing comment_post_ID should be recorded as a failure'
		);
		self::assertFalse(
			has_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ] ),
			'Nothing should be hooked for an item that is not a comment'
		);
	}
}
