<?php
/**
 * Integration tests for the scope of a spam verdict within one request.
 *
 * @package AntispamBee\Tests\Integration\Handlers
 */

namespace AntispamBee\Tests\Integration\Handlers;

use AntispamBee\Handlers\Reaction;
use AntispamBee\PostProcessors\SaveReason;
use AntispamBee\PostProcessors\SendEmail;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * A spam verdict and the work deferred to `wp_insert_comment` belong to the comment
 * that was checked, not to whatever comment is created next in the same request.
 */
final class SpamVerdictScopeTest extends TestCase {

	/**
	 * Post the comments are written to.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Mails sent during a test.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $mails = [];

	/**
	 * Create a post and catch outgoing mails before they reach PHPMailer.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->post_id = self::factory()->post->create();
		$this->mails   = [];

		add_filter( 'pre_wp_mail', [ $this, 'catch_mail' ], 10, 2 );
	}

	/**
	 * Remove anything a test left hooked.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'pre_comment_approved', [ Reaction::class, 'mark_as_spam' ] );
		remove_action( 'wp_insert_comment', [ SaveReason::class, 'save_reasons' ], 10 );
		remove_action( 'wp_insert_comment', [ SendEmail::class, 'send_notification' ], 10 );
		remove_filter( 'pre_wp_mail', [ $this, 'catch_mail' ], 10 );

		parent::tear_down();
	}

	public function test_the_spam_verdict_applies_to_one_comment_only(): void {
		add_filter( 'pre_comment_approved', [ Reaction::class, 'mark_as_spam' ] );

		self::assertSame( 'spam', apply_filters( 'pre_comment_approved', 1, [] ) );
		self::assertSame( 1, apply_filters( 'pre_comment_approved', 1, [] ), 'A second comment must not inherit the verdict' );
	}

	public function test_the_reasons_skip_a_comment_inserted_before_the_spam_comment(): void {
		SaveReason::process( [ 'asb_reasons' => [ 'asb-honeypot' ] ] );

		$other = $this->create_comment( '1' );
		$spam  = $this->create_comment( 'spam' );

		self::assertSame( '', get_comment_meta( $other, 'antispam_bee_reason', true ) );
		self::assertSame( 'asb-honeypot', get_comment_meta( $spam, 'antispam_bee_reason', true ) );
	}

	public function test_the_notification_skips_a_comment_inserted_before_the_spam_comment(): void {
		SendEmail::process(
			[
				'reaction_type' => 'comment',
				'asb_reasons'   => [ 'asb-honeypot' ],
			]
		);

		$this->create_comment( '1' );
		self::assertSame( [], $this->mails, 'No mail for a comment that is not spam' );

		$spam = $this->create_comment( 'spam' );

		self::assertCount( 1, $this->mails );
		self::assertStringContainsString( (string) $spam, $this->mails[0]['message'] );
	}

	/**
	 * Record a mail instead of sending it.
	 *
	 * @param null|bool            $result Short-circuit result.
	 * @param array<string, mixed> $atts   Mail arguments.
	 *
	 * @return bool Always true, so the mail counts as sent.
	 */
	public function catch_mail( $result, $atts ): bool {
		$this->mails[] = $atts;

		return true;
	}

	/**
	 * Insert a comment with the given status.
	 *
	 * @param string $status Approval status.
	 *
	 * @return int Comment ID.
	 */
	private function create_comment( string $status ): int {
		return (int) self::factory()->comment->create(
			[
				'comment_post_ID'  => $this->post_id,
				'comment_approved' => $status,
			]
		);
	}
}
