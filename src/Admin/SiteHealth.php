<?php
/**
 * Site Health tests.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\Honeypot as HoneypotField;
use AntispamBee\Rules\Honeypot as HoneypotRule;

/**
 * Report under Tools → Site Health whether the honeypot reaches the comment form.
 */
class SiteHealth {

	/**
	 * Identifier of the honeypot test.
	 *
	 * @var string
	 */
	const HONEYPOT_TEST = 'antispam_bee_honeypot';

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'site_status_tests', [ __CLASS__, 'add_tests' ] );
	}

	/**
	 * Register the tests.
	 *
	 * The honeypot test is only registered while the rule is active: an admin who
	 * switched it off chose to go without it.
	 *
	 * @param mixed $tests The registered tests.
	 *
	 * @return mixed The tests, including the ones of Antispam Bee.
	 */
	public static function add_tests( $tests ) {
		if ( ! is_array( $tests ) || ! HoneypotRule::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
			return $tests;
		}

		$tests['direct'][ self::HONEYPOT_TEST ] = [
			'label' => __( 'Antispam Bee honeypot', 'antispam-bee' ),
			'test'  => [ __CLASS__, 'test_honeypot' ],
		];

		return $tests;
	}

	/**
	 * Test whether the honeypot reaches the comment form.
	 *
	 * @return array<string, mixed> The test result.
	 */
	public static function test_honeypot(): array {
		$result = [
			'badge'       => [
				'label' => __( 'Security', 'antispam-bee' ),
				'color' => 'blue',
			],
			'actions'     => '',
			'test'        => self::HONEYPOT_TEST,
		];

		if ( HoneypotStatus::needs_attention() ) {
			return array_merge(
				$result,
				[
					'label'       => __( 'The Antispam Bee honeypot could not be added to your comment form', 'antispam-bee' ),
					'status'      => 'recommended',
					'description' => sprintf(
						'<p>%s</p><p>%s</p>',
						esc_html( HoneypotStatus::get_explanation() ),
						esc_html( HoneypotStatus::get_advice() )
					),
					'actions'     => sprintf(
						'<p><a href="%s">%s</a></p>',
						esc_url( HoneypotStatus::get_settings_url() ),
						esc_html__( 'Open the Antispam Bee comment settings', 'antispam-bee' )
					),
				]
			);
		}

		if ( HoneypotField::injection_observed() ) {
			return array_merge(
				$result,
				[
					'label'       => __( 'The Antispam Bee honeypot is in your comment form', 'antispam-bee' ),
					'status'      => 'good',
					'description' => sprintf(
						'<p>%s</p>',
						esc_html__( 'The last time a comment form was displayed, Antispam Bee added its honeypot to it, so bots posting comments without using the form are recognized.', 'antispam-bee' )
					),
				]
			);
		}

		return array_merge(
			$result,
			[
				'label'       => __( 'No comment form has been checked for the Antispam Bee honeypot yet', 'antispam-bee' ),
				'status'      => 'good',
				'description' => sprintf(
					'<p>%s</p>',
					esc_html__( 'Antispam Bee checks whether it could add its honeypot each time a comment form is displayed. So far, no comment form has been displayed and no comment has been submitted.', 'antispam-bee' )
				),
			]
		);
	}
}
