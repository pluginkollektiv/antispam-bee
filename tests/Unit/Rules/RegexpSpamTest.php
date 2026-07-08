<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\RegexpSpam;

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Filters\expectApplied;

class RegexpSpamTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( RegexpSpam::class, 'asb-regexp' );
	}

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		when( 'wp_parse_url' )->alias( 'parse_url' );
	}

	public function test_verify() {
		self::assertSame(
			0,
			RegexpSpam::verify( self::make_comment() ),
			'clean comment should not be flagged'
		);

		self::assertSame(
			0,
			RegexpSpam::verify( array( 'reaction_type' => 'unknown' ) ),
			'unknown reaction type should not be flagged'
		);

		$spam_author                   = self::make_comment();
		$spam_author['comment_author'] = 'Buy Viagra';
		self::assertSame(
			1,
			RegexpSpam::verify( $spam_author ),
			'known spam word in the author name should be flagged'
		);

		$spam_body                         = self::make_comment();
		$spam_body['comment_content']      = 'this is a pharmacy, why does it work now?.';
		$spam_body['comment_author_email'] = 'test@yandex.ru';
		self::assertSame(
			1,
			RegexpSpam::verify( $spam_body ),
			'matching body and email pattern combination should be flagged'
		);

		$partial_match                    = self::make_comment();
		$partial_match['comment_content'] = 'this is a pharmacy, why does it work now?.';
		self::assertSame(
			0,
			RegexpSpam::verify( $partial_match ),
			'a pattern combination should only match if all of its fields match'
		);
	}

	public function test_verify_respects_custom_patterns_filter() {
		$item                    = self::make_comment();
		$item['comment_content'] = 'A perfectly harmless custom message.';

		expectApplied( 'antispam_bee_patterns' )
			->once()
			->andReturn(
				[
					[
						'body' => 'harmless custom message',
					],
				]
			);

		self::assertSame(
			1,
			RegexpSpam::verify( $item ),
			'custom patterns added via the antispam_bee_patterns filter should be applied'
		);
	}
}
