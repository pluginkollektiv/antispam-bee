<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\LangSpam;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see LangSpam}.
 */
class LangSpamTest extends AbstractRuleTestCase {

	/**
	 * The spam comment from the bug report, written in Chinese.
	 *
	 * @var string
	 */
	private const CHINESE_SPAM = '致力于为开发者提供快速、便捷的企业级AI接口调用方案，打造稳定且易于使用的 API 接口平台，一站式集成几乎所有 AI 大模型。';

	/**
	 * The languages the rule allows, as stored by the checkbox group.
	 *
	 * @var array
	 */
	private $allowed_languages = [];

	public function __construct() {
		parent::__construct( LangSpam::class, 'asb-lang-spam' );
	}

	public function test_verify_does_not_call_the_service_without_content(): void {
		$this->expect_no_request();

		$item         = self::make_comment();
		$item['body'] = '';

		self::assertSame( 0, LangSpam::verify( $item ), 'A comment without content should not be flagged' );
	}

	public function test_verify_does_not_call_the_service_for_a_short_latin_text(): void {
		$this->expect_no_request();

		self::assertSame(
			0,
			LangSpam::verify( self::make_comment_with( 'A small text passes the test. Lets check this.' ) ),
			'A latin text with less than ten words should not be sent to the service'
		);
	}

	public function test_verify_flags_a_comment_in_a_script_without_word_delimiters(): void {
		$this->expect_request( '{"code":"cmn"}' );

		self::assertSame(
			1,
			LangSpam::verify( self::make_comment_with( self::CHINESE_SPAM ) ),
			'A Chinese comment should be sent to the service and flagged, because only German is allowed'
		);
	}

	public function test_verify_does_not_call_the_service_for_a_very_short_chinese_text(): void {
		$this->expect_no_request();

		self::assertSame(
			0,
			LangSpam::verify( self::make_comment_with( '中文垃圾' ) ),
			'A text with less than ten characters should not be sent to the service'
		);
	}

	public function test_verify_does_not_call_the_service_for_a_dominantly_latin_text(): void {
		$this->expect_no_request();

		self::assertSame(
			0,
			LangSpam::verify( self::make_comment_with( 'Great article thanks for sharing 中文垃圾评论' ) ),
			'A few characters of another script should not make a latin text be sent to the service'
		);
	}

	public function test_verify_measures_a_dominantly_latin_text_in_words(): void {
		$this->expect_request( '{"code":"eng"}' );

		// The handful of Chinese characters does not carry the text, so the character
		// threshold does not apply and the word count decides - and this text has
		// enough words. Guards the fall-through out of the spaceless-script branch:
		// returning early there instead would stop such a text being checked at all.
		self::assertSame(
			1,
			LangSpam::verify(
				self::make_comment_with( 'Great article thanks for sharing this with all of us here 中文垃圾评论' )
			),
			'A long latin text with a few characters of another script should be sent to the service'
		);
	}

	public function test_verify_does_not_flag_an_undetermined_language(): void {
		$this->expect_request( '{"code":"und"}' );

		self::assertSame(
			0,
			LangSpam::verify( self::make_comment_with( self::CHINESE_SPAM ) ),
			'A language the service could not determine should not be flagged'
		);
	}

	public function test_verify_maps_the_chinese_macrolanguage(): void {
		$this->expect_request( '{"code":"cmn"}' );
		$this->allowed_languages = [ 'zh' => 'on' ];

		self::assertSame(
			0,
			LangSpam::verify( self::make_comment_with( self::CHINESE_SPAM ) ),
			'A Chinese comment should not be flagged if Chinese is allowed'
		);
	}

	public function test_verify_respects_the_minimum_words_filter(): void {
		$this->expect_request( '{"code":"eng"}' );

		expectApplied( 'antispam_bee_lang_min_words' )
			->once()
			->andReturn( 3 );

		self::assertSame(
			1,
			LangSpam::verify( self::make_comment_with( 'Just four little words.' ) ),
			'A lowered word threshold should let a shorter text be sent to the service'
		);
	}

	public function test_verify_respects_the_minimum_characters_filter(): void {
		$this->expect_request( '{"code":"cmn"}' );

		expectApplied( 'antispam_bee_lang_min_characters' )
			->once()
			->andReturn( 4 );

		self::assertSame(
			1,
			LangSpam::verify( self::make_comment_with( '中文垃圾' ) ),
			'A lowered character threshold should let a shorter text be sent to the service'
		);
	}

	public function test_verify_detected_lang_filter_skips_the_length_check(): void {
		$this->expect_no_request();

		expectApplied( 'antispam_bee_detected_lang' )
			->once()
			->andReturn( 'zh' );

		self::assertSame(
			1,
			LangSpam::verify( self::make_comment_with( '中文' ) ),
			'A language detected by the filter should be used, no matter how short the text is'
		);
	}

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		if ( ! defined( 'AntispamBee\MAIN_PLUGIN_FILE' ) ) {
			define( 'AntispamBee\MAIN_PLUGIN_FILE', dirname( __DIR__, 3 ) . '/antispam_bee.php' );
		}

		when( 'wp_strip_all_tags' )->returnArg();
		when( 'wp_json_encode' )->alias( 'json_encode' );
		when( 'is_wp_error' )->justReturn( false );
		when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		// Keep the plugin update logic, which the settings run into, from touching the database.
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0' ] );

		$this->allowed_languages = [ 'de' => 'on' ];

		when( 'get_option' )->alias(
			function ( string $option ) {
				if ( 'antispambee_db_version' === $option ) {
					return '3.0.0';
				}

				return [
					'comment' => [
						'rule_asb_lang_spam_allowed' => $this->allowed_languages,
					],
				];
			}
		);
	}

	/**
	 * Generate a comment with the given content.
	 *
	 * @param string $content The comment content.
	 *
	 * @return array Comment array.
	 */
	private static function make_comment_with( string $content ): array {
		return self::make_comment( 1, 'Test Author', 'test.author@example.com', 'www.example.com', '192.0.2.1', $content );
	}

	/**
	 * Expect a single request to the detection service.
	 *
	 * @param string $body The response body to return.
	 *
	 * @return void
	 */
	private function expect_request( string $body ): void {
		expect( 'wp_safe_remote_post' )->once()->andReturn( [ 'body' => $body ] );
		when( 'wp_remote_retrieve_body' )->justReturn( $body );
	}

	/**
	 * Expect that the detection service is not called at all.
	 *
	 * @return void
	 */
	private function expect_no_request(): void {
		expect( 'wp_safe_remote_post' )->never();
	}
}
