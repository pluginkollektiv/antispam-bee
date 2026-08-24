<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\CountrySpam;
use function Brain\Monkey\Filters\expectApplied;
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

	/**
	 * The country codes the rule allows, as stored by the settings.
	 *
	 * @var string
	 */
	private $allowed_countries = '';

	/**
	 * The URL the last expected request went to.
	 *
	 * @var string
	 */
	private $request_url = '';

	/**
	 * The arguments the last expected request was made with.
	 *
	 * @var array<string, mixed>
	 */
	private $request_args = [];

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

	/**
	 * The filter can return an empty string to decline an address, which must not
	 * result in a lookup for no address at all.
	 */
	public function test_verify_does_not_call_the_service_for_an_empty_filtered_ip(): void {
		$this->expect_no_request();

		expectApplied( 'antispam_bee_country_spam_ip' )
			->once()
			->andReturn( '' );

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'An address the filter declined should not be sent to the service'
		);
	}

	/**
	 * A site that would rather trade privacy for a more precise country can return
	 * the original address from the filter. It receives the anonymized address and
	 * the original one to choose from.
	 */
	public function test_verify_looks_up_the_address_the_filter_returns(): void {
		$this->expect_request( '{"country_code":"DE"}' );

		expectApplied( 'antispam_bee_country_spam_ip' )
			->once()
			->with( '198.51.100.0', '198.51.100.42' )
			->andReturn( '198.51.100.42' );

		self::assertSame(
			1,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'The address returned by the filter should be used for the lookup'
		);
		self::assertSame(
			'https://www.iplocate.io/api/lookup/198.51.100.42',
			$this->request_url,
			'The address returned by the filter should be the one in the lookup URL'
		);
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

	public function test_verify_flags_a_country_outside_the_allow_list(): void {
		$this->expect_request( '{"country_code":"US"}' );
		$this->denied_countries  = '';
		$this->allowed_countries = 'FR';

		self::assertSame(
			1,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'With only an allow list, a country outside it should be flagged'
		);
	}

	public function test_verify_does_not_flag_a_country_on_the_allow_list(): void {
		$this->expect_request( '{"country_code":"FR"}' );
		$this->denied_countries  = '';
		$this->allowed_countries = 'FR';

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'With only an allow list, a country on it should not be flagged'
		);
	}

	/**
	 * The deny list used to return early, so with both lists configured a country
	 * on neither list was treated as ham — even though an allow list means "only
	 * these are ham".
	 */
	public function test_verify_flags_a_country_on_neither_list_when_both_are_configured(): void {
		$this->expect_request( '{"country_code":"US"}' );
		$this->denied_countries  = 'DE';
		$this->allowed_countries = 'FR';

		self::assertSame(
			1,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'With both lists configured, a country outside the allow list should still be flagged'
		);
	}

	public function test_verify_does_not_flag_an_allowed_country_when_both_lists_are_configured(): void {
		$this->expect_request( '{"country_code":"FR"}' );
		$this->denied_countries  = 'DE';
		$this->allowed_countries = 'FR';

		self::assertSame(
			0,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'A country on the allow list and absent from the deny list should not be flagged'
		);
	}

	public function test_verify_flags_a_denied_country_when_both_lists_are_configured(): void {
		$this->expect_request( '{"country_code":"DE"}' );
		$this->denied_countries  = 'DE';
		$this->allowed_countries = 'FR';

		self::assertSame(
			1,
			CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ),
			'The deny list should still apply when an allow list is configured'
		);
	}

	/**
	 * Without a key, the service answers anonymous lookups, but rejects an empty
	 * `apikey` parameter with `401 Invalid API key`.
	 *
	 * @see https://github.com/pluginkollektiv/antispam-bee/issues/819
	 */
	public function test_verify_does_not_send_an_empty_api_key(): void {
		$this->expect_request( '{"country_code":"DE"}' );

		self::assertSame( 1, CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ) );

		self::assertStringNotContainsString(
			'apikey',
			$this->request_url,
			'Without a key, the lookup URL should not carry an `apikey` parameter'
		);
		self::assertSame(
			[],
			$this->request_args,
			'Without a key, the request should not carry any headers'
		);
	}

	public function test_verify_sends_a_configured_api_key_as_a_header(): void {
		expectApplied( 'antispam_bee_country_spam_apikey' )->once()->andReturn( ' secret-key ' );

		$this->expect_request( '{"country_code":"DE"}' );

		self::assertSame( 1, CountrySpam::verify( self::make_comment_from( '198.51.100.42' ) ) );

		self::assertStringNotContainsString(
			'secret-key',
			$this->request_url,
			'The key should stay out of the URL, and with it out of proxy and server logs'
		);
		self::assertSame(
			[ 'headers' => [ 'X-Api-Key' => 'secret-key' ] ],
			$this->request_args,
			'The key should be sent as a header, trimmed'
		);
	}

	/**
	 * The `sanitize` callbacks are handed the posted value as it arrived. The two
	 * country lists are textareas, but a request can post an array for them, and
	 * that used to reach a `string` parameter and raise a `TypeError` — a fatal
	 * while saving the settings, from the code whose job is to reject bad input.
	 */
	public function test_the_country_list_sanitizers_discard_a_value_that_is_not_a_string(): void {
		$sanitizers = [];
		foreach ( CountrySpam::get_options() as $option ) {
			$option_name = $option->get_option_name();
			$sanitize    = $option->get_sanitize();
			if ( null !== $sanitize && in_array( $option_name, [ 'denied', 'allowed' ], true ) ) {
				$sanitizers[ $option_name ] = $sanitize;
			}
		}

		self::assertCount( 2, $sanitizers, 'Both country lists should expose a sanitize callback' );

		foreach ( $sanitizers as $option_name => $sanitize ) {
			foreach ( [ 'an array' => [ 'DE' ], 'null' => null, 'a number' => 42 ] as $description => $value ) {
				self::assertSame(
					'',
					$sanitize( $value ),
					"The $option_name list should discard $description instead of raising a TypeError"
				);
			}
		}
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

		$this->denied_countries  = 'DE';
		$this->allowed_countries = '';

		when( 'get_option' )->alias(
			function ( string $option ) {
				if ( 'antispambee_db_version' === $option ) {
					return '3.0.0';
				}

				return [
					'comment' => [
						'rule_asb_country_spam_denied'  => $this->denied_countries,
						'rule_asb_country_spam_allowed' => $this->allowed_countries,
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
		expect( 'wp_safe_remote_get' )
			->once()
			->andReturnUsing(
				function ( string $url, array $args = [] ) use ( $body ) {
					$this->request_url  = $url;
					$this->request_args = $args;

					return [ 'body' => $body ];
				}
			);
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
