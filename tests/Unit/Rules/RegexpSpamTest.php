<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\RegexpSpam;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\when;

class RegexpSpamTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( RegexpSpam::class, 'asb-regexp' );
	}

	public function test_verify() {
		self::assertSame(
			0,
			RegexpSpam::verify( self::make_comment() ),
			'Clean comment should not be flagged'
		);

		self::assertSame(
			0,
			RegexpSpam::verify( [ 'reaction_type' => 'unknown' ] ),
			'Unknown reaction type should not be flagged'
		);

		$spam_author           = self::make_comment();
		$spam_author['author'] = 'Buy Viagra';
		self::assertSame(
			1,
			RegexpSpam::verify( $spam_author ),
			'Known spam word in the author name should be flagged'
		);

		$spam_body          = self::make_comment();
		$spam_body['body']  = 'this is a pharmacy, why does it work now?.';
		$spam_body['email'] = 'test@yandex.ru';
		self::assertSame(
			1,
			RegexpSpam::verify( $spam_body ),
			'Matching body and email pattern combination should be flagged'
		);

		$partial_match         = self::make_comment();
		$partial_match['body'] = 'this is a pharmacy, why does it work now?.';
		self::assertSame(
			0,
			RegexpSpam::verify( $partial_match ),
			'A pattern combination should only match if all of its fields match'
		);
	}

	public function test_verify_respects_custom_patterns_filter() {
		$item         = self::make_comment();
		$item['body'] = 'A perfectly harmless custom message.';

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
			'Custom patterns added via the antispam_bee_patterns filter should be applied'
		);
	}

	/**
	 * The `porn`/`pornstar`/`20bet` author terms must be detected.
	 */
	public function test_verify_author_terms() {
		$porn           = self::make_comment();
		$porn['author'] = 'freehdporn';
		self::assertSame( 1, RegexpSpam::verify( $porn ), 'A porn author term should be flagged' );

		$bet           = self::make_comment();
		$bet['author'] = '20bet';
		self::assertSame( 1, RegexpSpam::verify( $bet ), 'The 20bet author term should be flagged' );
	}

	/**
	 * A Binance referral registration URL must be detected via the `rawurl`
	 * pattern.
	 *
	 * Regression test: the rule declares a `rawurl` subject and pattern, but the
	 * field was missing from the `$fields` allow-list, so the pattern never fired.
	 */
	public function test_verify_binance_rawurl() {
		$item        = self::make_comment();
		$item['url'] = 'https://accounts.binance.com/en/register?ref=ABCDE123';
		self::assertSame( 1, RegexpSpam::verify( $item ), 'A Binance referral URL should be flagged via rawurl' );
	}

	/**
	 * A non-referral Binance URL must not match, guarding the pattern's specificity.
	 */
	public function test_verify_binance_non_referral() {
		$item        = self::make_comment();
		$item['url'] = 'https://www.binance.com/en/support';
		self::assertSame( 0, RegexpSpam::verify( $item ), 'A non-referral Binance URL should not be flagged' );
	}

	/**
	 * An author that is not valid UTF-8 must not raise a warning.
	 *
	 * Regression test for #587: the author is spliced into three of the patterns,
	 * so malformed bytes travel into the pattern rather than the subject, and the
	 * `u` modifier then fails to *compile* it — which warns instead of not matching.
	 * `\xC0\xAF` is an overlong encoding of `/`, the sequence class from the report.
	 */
	public function test_verify_author_with_invalid_utf8() {
		$item           = self::make_comment();
		$item['author'] = "Spam\xC0\xAFName";

		self::assertSame(
			0,
			RegexpSpam::verify( $item ),
			'An author that is not valid UTF-8 should be handled without a warning'
		);
	}

	/**
	 * A body that is not valid UTF-8 must not raise a warning either.
	 *
	 * The former `iconv( 'utf-8', 'utf-8//TRANSLIT', … )` sanitisation warned on
	 * exactly this input and returned `false`, so it never sanitised anything.
	 */
	public function test_verify_body_with_invalid_utf8() {
		$item         = self::make_comment();
		$item['body'] = "Harmless \xE2\x28\xA1 text";

		self::assertSame(
			0,
			RegexpSpam::verify( $item ),
			'A body that is not valid UTF-8 should be handled without a warning'
		);
	}

	/**
	 * Repairing the author must not cost detection: a spam term next to malformed
	 * bytes still has to be flagged.
	 */
	public function test_verify_still_detects_spam_author_with_invalid_utf8() {
		$item           = self::make_comment();
		$item['author'] = "Buy Viagra\xC0\xAF";

		self::assertSame(
			1,
			RegexpSpam::verify( $item ),
			'A known spam term should still be flagged when the author also carries malformed bytes'
		);
	}

	/**
	 * A pattern supplied through the filter can be malformed too, and must be
	 * skipped rather than allowed to fail compilation.
	 */
	public function test_verify_skips_custom_patterns_that_are_not_valid_utf8() {
		$item         = self::make_comment();
		$item['body'] = 'A perfectly harmless custom message.';

		expectApplied( 'antispam_bee_patterns' )
			->once()
			->andReturn(
				[
					[
						'body' => "harmless\xC0\xAF custom message",
					],
				]
			);

		self::assertSame(
			0,
			RegexpSpam::verify( $item ),
			'A custom pattern that is not valid UTF-8 should be skipped without a warning'
		);
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
}
