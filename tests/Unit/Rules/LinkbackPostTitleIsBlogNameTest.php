<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\LinkbackPostTitleIsBlogName;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Unit tests for {@see LinkbackPostTitleIsBlogName}.
 */
class LinkbackPostTitleIsBlogNameTest extends TestCase {

	public function test_verify_flags_a_linkback_whose_title_is_the_blog_name(): void {
		self::assertSame(
			999,
			LinkbackPostTitleIsBlogName::verify(
				[
					'body'   => "text <strong>My Blog</strong>\n\nmore text",
					'author' => 'My Blog',
				]
			),
			'A linkback whose linked post title equals the blog name should be flagged'
		);
	}

	public function test_verify_ignores_surrounding_whitespace_when_comparing(): void {
		self::assertSame(
			999,
			LinkbackPostTitleIsBlogName::verify(
				[
					'body'   => "<strong>  My Blog </strong>\n\n",
					'author' => ' My Blog  ',
				]
			),
			'The comparison should not be defeated by surrounding whitespace'
		);
	}

	public function test_verify_does_not_flag_a_different_title(): void {
		self::assertSame(
			0,
			LinkbackPostTitleIsBlogName::verify(
				[
					'body'   => "<strong>Some Post</strong>\n\n",
					'author' => 'My Blog',
				]
			),
			'A linkback to a post with another title should not be flagged'
		);
	}

	public function test_verify_does_not_flag_a_body_without_a_title(): void {
		self::assertSame(
			0,
			LinkbackPostTitleIsBlogName::verify(
				[
					'body'   => 'no markup here',
					'author' => 'My Blog',
				]
			),
			'Without a title in the body there is nothing to compare'
		);
	}

	/**
	 * The payload is not guaranteed to carry either attribute. Both used to be
	 * read with a `null` fallback and passed straight to `preg_match()` and
	 * `trim()`, which is deprecated for `null` on PHP 8.1+.
	 */
	public function test_verify_handles_a_missing_body_or_author(): void {
		$payloads = [
			'both missing'   => [],
			'missing body'   => [ 'author' => 'My Blog' ],
			'missing author' => [ 'body' => "<strong>My Blog</strong>\n\n" ],
			'null body'      => [
				'body'   => null,
				'author' => 'My Blog',
			],
			'null author'    => [
				'body'   => "<strong>My Blog</strong>\n\n",
				'author' => null,
			],
		];

		foreach ( $payloads as $description => $payload ) {
			self::assertSame(
				0,
				LinkbackPostTitleIsBlogName::verify( $payload ),
				"A payload with $description should not be flagged and must not raise a deprecation"
			);
		}
	}
}
