<?php

namespace AntispamBee\Tests\Unit\Rules;

use AntispamBee\Rules\ValidGravatar;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Unit tests for {@see ValidGravatar}.
 */
class ValidGravatarTest extends AbstractRuleTestCase {

	public function __construct() {
		parent::__construct( ValidGravatar::class, 'asb-valid-gravatar' );
	}

	/**
	 * v2 gated this lookup on the site's avatar setting. Without the guard a site
	 * that switched avatars off still sends a hash of every commenter's address
	 * to a third party.
	 */
	public function test_no_lookup_happens_when_avatars_are_disabled() {
		when( 'wp_unslash' )->returnArg();
		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return 'show_avatars' === $name ? 0 : $default;
			}
		);
		expect( 'wp_safe_remote_get' )->never();

		self::assertSame(
			0,
			ValidGravatar::verify( [ 'email' => 'someone@example.com' ] ),
			'the rule should stay silent while avatars are disabled'
		);
	}

	/**
	 * The payload arrives slashed, and Gravatar hashes the address as the visitor
	 * typed it, so an apostrophe has to be unslashed before hashing or the
	 * address can never match.
	 */
	public function test_the_email_is_unslashed_before_it_is_hashed() {
		when( 'wp_unslash' )->alias( 'stripslashes' );
		when( 'get_option' )->alias(
			function ( $name, $default = false ) {
				return 'show_avatars' === $name ? 1 : $default;
			}
		);
		when( 'is_wp_error' )->justReturn( false );
		when( 'wp_remote_retrieve_response_code' )->justReturn( 404 );

		$expected = md5( "o'brien@example.com" );

		expect( 'wp_safe_remote_get' )
			->once()
			->with( 'https://www.gravatar.com/avatar/' . $expected . '?d=404' )
			->andReturn( [] );

		ValidGravatar::verify( [ 'email' => "o\\'brien@example.com" ] );
	}
}
