<?php

namespace AntispamBee\Tests\Unit\Helpers;

use AntispamBee\Helpers\SpamReasonTextHelper;
use Yoast\WPTestUtils\BrainMonkey\TestCase;
use function Brain\Monkey\Functions\stubs;

/**
 * Unit tests for {@see SpamReasonTextHelper}.
 */
class SpamReasonTextHelperTest extends TestCase {

	/**
	 * Stub the escaping functions with their real escaping behaviour.
	 *
	 * The point of these tests is that the helper escapes, so stubbing the
	 * `esc_*` functions as pass-throughs would defeat them.
	 *
	 * @return void
	 */
	private function stub_escaping(): void {
		stubs(
			[
				'esc_html'   => function ( string $text ) {
					return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
				},
				'esc_html_x' => function ( string $text ) {
					return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
				},
			]
		);
	}

	public function test_unknown_slug_is_escaped(): void {
		$this->stub_escaping();

		self::assertSame(
			[ 'Unknown rule: &lt;script&gt;alert(1)&lt;/script&gt;' ],
			SpamReasonTextHelper::get_texts_by_slugs( [ '<script>alert(1)</script>' ] ),
			'An unknown rule slug should be escaped before it is interpolated into the text'
		);
	}

	public function test_unknown_slug_leaves_harmless_values_readable(): void {
		$this->stub_escaping();

		self::assertSame(
			[ 'Unknown rule: some_removed_rule' ],
			SpamReasonTextHelper::get_texts_by_slugs( [ 'some_removed_rule' ] ),
			'A harmless unknown slug should still be rendered verbatim'
		);
	}

	public function test_every_returned_text_is_escaped(): void {
		$this->stub_escaping();

		$texts = SpamReasonTextHelper::get_texts_by_slugs(
			[ 'server', '"><img src=x onerror=alert(1)>' ]
		);

		foreach ( $texts as $text ) {
			self::assertDoesNotMatchRegularExpression(
				'/[<>"]/',
				$text,
				'Callers echo these texts unescaped, so no returned text may contain raw markup characters'
			);
		}
	}
}
