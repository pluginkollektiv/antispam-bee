<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\ApprovedEmail;
use function Brain\Monkey\Functions\when;

class ApprovedEmailTest extends AbstractRuleTestCase {

	/**
	 * The author name / email pair the fake database has an approved comment for.
	 *
	 * @var array<string, string>
	 */
	private const APPROVED = [
		'author' => 'Test Author',
		'email'  => 'test.author@example.com',
	];

	public function __construct() {
		parent::__construct( ApprovedEmail::class, 'asb-approved-email' );
	}

	/**
	 * Stub the functions the rule uses, backed by a fake $wpdb that only knows
	 * about the single approved author/email pair in self::APPROVED.
	 *
	 * @param object|false $user The user get_user_by() should return.
	 * @param int          $user_comment_count Approved comments for that user.
	 *
	 * @return void
	 */
	private function stub_wordpress( $user = false, int $user_comment_count = 0 ): void {
		when( 'wp_unslash' )->returnArg();
		when( 'get_user_by' )->justReturn( $user );
		when( 'get_comments' )->justReturn( $user_comment_count );

		$GLOBALS['wpdb'] = new class() {
			public $comments = 'wp_comments';

			/**
			 * Substitute the %s placeholders so get_var() can inspect the values.
			 */
			public function prepare( $query, ...$args ) {
				foreach ( $args as $arg ) {
					$query = preg_replace( '/%s/', "'" . $arg . "'", $query, 1 );
				}

				return $query;
			}

			/**
			 * Return a comment ID only for the one approved author/email pair.
			 */
			public function get_var( $query ) {
				$author = "`comment_author` = '" . ApprovedEmailTest::approved_author() . "'";
				$email  = "`comment_author_email` = '" . ApprovedEmailTest::approved_email() . "'";

				return ( false !== strpos( $query, $author ) && false !== strpos( $query, $email ) ) ? 1 : null;
			}
		};
	}

	public static function approved_author(): string {
		return self::APPROVED['author'];
	}

	public static function approved_email(): string {
		return self::APPROVED['email'];
	}

	/**
	 * An anonymous commenter has to supply the approved name *and* email address.
	 */
	public function test_verify_matches_author_and_email() {
		$this->stub_wordpress();

		$item = self::make_comment( 1, self::APPROVED['author'], self::APPROVED['email'] );

		self::assertSame( -100, ApprovedEmail::verify( $item ), 'An approved author/email pair should be trusted' );
	}

	/**
	 * The regression this rule change exists for: knowing an approved email
	 * address must no longer be enough on its own.
	 */
	public function test_verify_rejects_matching_email_with_different_author() {
		$this->stub_wordpress();

		$item = self::make_comment( 1, 'Someone Else', self::APPROVED['email'] );

		self::assertSame( 0, ApprovedEmail::verify( $item ), 'A known email address alone must not be trusted' );
	}

	/**
	 * An approved author name paired with an unknown address is not trusted either.
	 */
	public function test_verify_rejects_matching_author_with_different_email() {
		$this->stub_wordpress();

		$item = self::make_comment( 1, self::APPROVED['author'], 'someone.else@example.com' );

		self::assertSame( 0, ApprovedEmail::verify( $item ), 'A known author name alone must not be trusted' );
	}

	/**
	 * A registered user is matched by ID, so the submitted name does not matter.
	 */
	public function test_verify_matches_registered_user_by_id() {
		$user = (object) [ 'ID' => 7 ];

		$this->stub_wordpress( $user, 3 );
		$item = self::make_comment( 1, 'Any Display Name', 'registered@example.com' );
		self::assertSame( -100, ApprovedEmail::verify( $item ), 'A registered user with approved comments should be trusted' );

		$this->stub_wordpress( $user, 0 );
		self::assertSame( 0, ApprovedEmail::verify( $item ), 'A registered user without approved comments should not be trusted' );
	}

	/**
	 * Core requires both values to be present before it trusts anyone.
	 */
	public function test_verify_requires_author_and_email_to_be_present() {
		$this->stub_wordpress();

		self::assertSame( 0, ApprovedEmail::verify( self::make_comment( 1, '', self::APPROVED['email'] ) ), 'An empty author name should not be trusted' );
		self::assertSame( 0, ApprovedEmail::verify( self::make_comment( 1, self::APPROVED['author'], '' ) ), 'An empty email address should not be trusted' );
		self::assertSame( 0, ApprovedEmail::verify( [] ), 'An empty payload should not be trusted' );
	}
}
