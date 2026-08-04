<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\CountrySpam;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see CountrySpam}.
 */
class CountrySpamTest extends AbstractRuleTestCase {

	/**
	 * The country codes the rule denies, as stored by the settings.
	 *
	 * @var string
	 */
	private $denied_countries = '';

	public function __construct() {
		parent::__construct( CountrySpam::class, 'asb-country-spam' );
	}

	public function test_verify_does_not_call_the_service_without_an_ip(): void {
		$this->expect_no_request();

		$item       = self::make_comment();
		$item['ip'] = '';

		self::assertSame( 0, CountrySpam::verify( $item ), 'A reaction without an IP should not be flagged' );
	}

	public function test_verify_does_not_call_the_service_without_configured_countries(): void {
		$this->expect_no_request();
		$this->denied_countries = '';

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'Without a country list there is nothing to check'
		);
	}

	/**
	 * A local install reports the loopback address, and a site behind a reverse
	 * proxy without a `pre_comment_user_ip` filter sees a private address. Neither
	 * has a country, so the service must not be asked about them.
	 *
	 * The addresses are looped over instead of coming from a data provider,
	 * because {@see AbstractRuleTestCase} takes over the constructor PHPUnit
	 * would pass the provided data to.
	 */
	public function test_verify_does_not_call_the_service_for_a_non_global_ip(): void {
		$this->expect_no_request();

		$non_global_ips = [
			'IPv4 loopback'    => '127.0.0.1',
			'IPv6 loopback'    => '::1',
			'private IPv4'     => '10.11.12.13',
			'private IPv4 too' => '192.168.1.50',
			'link-local IPv6'  => 'fe80::1',
			'not an IP at all' => 'no-ip-at-all',
		];

		foreach ( $non_global_ips as $description => $ip ) {
			self::assertSame(
				0,
				CountrySpam::verify( self::make_comment_from( $ip ) ),
				"A $description address should not be sent to the service"
			);
		}
	}

	public function test_verify_flags_a_reaction_from_a_denied_country(): void {
		$this->expect_request( '{"country_code":"DE"}' );

		self::assertSame(
			1,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'A reaction from a denied country should be flagged'
		);
	}

	public function test_verify_does_not_flag_a_reaction_from_another_country(): void {
		$this->expect_request( '{"country_code":"FR"}' );

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'A reaction from a country that is not denied should not be flagged'
		);
	}

	public function test_verify_does_not_flag_an_undetermined_country(): void {
		$this->expect_request( '{}' );

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'A response without a country code should not be flagged'
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

		when( 'esc_url_raw' )->returnArg();
		when( 'is_wp_error' )->justReturn( false );
		when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		// Keep the plugin update logic, which the settings run into, from touching the database.
		when( 'get_file_data' )->justReturn( [ 'Version' => '3.0.0' ] );

		$this->denied_countries = 'DE';

		when( 'get_option' )->alias(
			function ( string $option ) {
				if ( 'antispambee_db_version' === $option ) {
					return '3.0.0';
				}

				return [
					'comment' => [
						'rule_asb_country_spam_denied' => $this->denied_countries,
					],
				];
			}
		);
	}

	/**
	 * Generate a comment payload from the given IP address.
	 *
	 * @param string $ip The IP address of the author.
	 *
	 * @return array Comment array.
	 */
	private static function make_comment_from( string $ip ): array {
		return self::make_comment( 1, 'Test Author', 'test.author@example.com', 'www.example.com', $ip );
	}

	/**
	 * Expect a single request to the geolocation service.
	 *
	 * @param string $body The response body to return.
	 *
	 * @return void
	 */
	private function expect_request( string $body ): void {
		expect( 'wp_safe_remote_get' )->once()->andReturn( [ 'body' => $body ] );
		when( 'wp_remote_retrieve_body' )->justReturn( $body );
	}

	/**
	 * Expect that the geolocation service is not called at all.
	 *
	 * @return void
	 */
	private function expect_no_request(): void {
		expect( 'wp_safe_remote_get' )->never();
	}
}
