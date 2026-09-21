<?php
/**
 * Public entry point for third-party spam checks.
 *
 * @package AntispamBee\Api
 */

namespace AntispamBee\Api;

use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\DataHelper;
use AntispamBee\Helpers\DebugMode;
use AntispamBee\Helpers\IpHelper;
use AntispamBee\Helpers\SpamReasonTextHelper;
use ReflectionException;

/**
 * Public entry point for third-party spam checks.
 *
 * Plugins that handle their own content — contact forms, registrations, custom
 * post types — can use this class to have Antispam Bee’s rules classify that
 * content, without depending on the plugin’s internal handlers.
 *
 * Everything outside `AntispamBee\Api` and the documented hooks is internal and
 * may change in any release.
 *
 * Typical use:
 *
 *     if ( class_exists( '\AntispamBee\Api\SpamCheck' ) ) {
 *         $result = \AntispamBee\Api\SpamCheck::check(
 *             [
 *                 'author' => $name,
 *                 'body'   => $message,
 *                 'email'  => $email,
 *             ],
 *             'my_plugin_form'
 *         );
 *
 *         if ( $result->is_spam() ) {
 *             // Store the reason slugs for later display.
 *             $reasons = $result->get_reasons();
 *         }
 *     }
 *
 * @since 3.0.0
 */
final class SpamCheck {

	/**
	 * Version of this API.
	 *
	 * Incremented only for breaking changes, so integrations can adapt.
	 *
	 * @var int
	 */
	const API_VERSION = 1;

	/**
	 * Check an item for spam.
	 *
	 * The item is normalized before the rules see it, so callers only need to
	 * provide the attributes their content actually has. Missing attributes
	 * default to an empty value; `host` is derived from `url`, and `ip` and
	 * `useragent` fall back to the current request.
	 *
	 * @param array<string, mixed> $item          Item attributes. Recognized keys are
	 *                                            `author`, `body`, `email`, `url`, `host`,
	 *                                            `ip`, `useragent` and `post_id`.
	 * @param string               $reaction_type The reaction type to check against. Register
	 *                                            a custom one via `antispam_bee_reaction_types`
	 *                                            and opt rules into it via
	 *                                            `antispam_bee_rule_supported_types`.
	 *
	 * @return CheckResult The outcome of the check.
	 */
	public static function check( array $item, string $reaction_type ): CheckResult {
		$payload = self::normalize( $item, $reaction_type );

		try {
			$active_rules = Rules::get( $reaction_type, true );
		} catch ( ReflectionException $exception ) {
			DebugMode::log( "SpamCheck: could not collect rules for reaction type {$reaction_type}: " . $exception->getMessage() );

			return CheckResult::not_evaluated( $payload );
		}

		if ( [] === $active_rules ) {
			DebugMode::log( "SpamCheck: no active rule for reaction type {$reaction_type}, item was not evaluated." );

			return CheckResult::not_evaluated( $payload );
		}

		$rules = new Rules( $reaction_type );

		try {
			$is_spam = $rules->apply( $payload );
		} catch ( ReflectionException $exception ) {
			DebugMode::log( "SpamCheck: could not apply rules for reaction type {$reaction_type}: " . $exception->getMessage() );

			return CheckResult::not_evaluated( $payload );
		}

		return new CheckResult(
			$is_spam,
			$is_spam ? $rules->get_spam_reasons() : [],
			$payload
		);
	}

	/**
	 * Whether at least one rule is active for a reaction type.
	 *
	 * Useful to warn administrators that a custom reaction type has no rule
	 * enabled yet, which would let every item pass.
	 *
	 * @param string $reaction_type The reaction type.
	 *
	 * @return bool Whether at least one rule is active.
	 */
	public static function has_active_rules( string $reaction_type ): bool {
		try {
			return [] !== Rules::get( $reaction_type, true );
		} catch ( ReflectionException $exception ) {
			return false;
		}
	}

	/**
	 * Get human-readable texts for a list of spam reason slugs.
	 *
	 * Use this to resolve slugs that were stored earlier, for example when
	 * rendering them in an admin table. Only useful once the `init` action has
	 * run, because the underlying slug map is populated on `init`.
	 *
	 * @param string[] $slugs A list of rule slugs.
	 *
	 * @return string[] A list of reason texts.
	 */
	public static function get_reason_texts( array $slugs ): array {
		if ( [] === $slugs ) {
			return [];
		}

		return SpamReasonTextHelper::get_texts_by_slugs( $slugs );
	}

	/**
	 * Run the post-processors for an item that was classified as spam.
	 *
	 * Optional second half of the flow. Call this to have Antispam Bee react to
	 * the spam the way it reacts to its own — updating the spam counter and
	 * statistics, sending notifications, and so on. Skip it if the calling
	 * plugin handles storage and notification itself.
	 *
	 * Post-processors must opt into the reaction type via
	 * `antispam_bee_post_processor_supported_types`.
	 *
	 * @param CheckResult          $result        The result returned by {@see self::check()}.
	 * @param array<string, mixed> $reaction      The caller’s own representation of the item.
	 * @param string               $reaction_type The reaction type.
	 *
	 * @return array<string, mixed> The processed item. A set `asb_marked_as_delete`
	 *                              key means the post-processors decided the item
	 *                              should be discarded rather than stored.
	 */
	public static function post_process( CheckResult $result, array $reaction, string $reaction_type ): array {
		if ( ! $result->is_spam() ) {
			return $reaction;
		}

		return PostProcessors::apply( $reaction_type, $reaction, $result->get_reasons() );
	}

	/**
	 * Normalize a caller-supplied item into the payload the rules expect.
	 *
	 * @param array<string, mixed> $item          Item attributes.
	 * @param string               $reaction_type The reaction type.
	 *
	 * @return array<string, mixed> The normalized payload.
	 */
	private static function normalize( array $item, string $reaction_type ): array {
		$payload = [
			'reaction_type' => $reaction_type,
			'author'        => (string) ( $item['author'] ?? '' ),
			'body'          => (string) ( $item['body'] ?? '' ),
			'email'         => (string) ( $item['email'] ?? '' ),
			'url'           => (string) ( $item['url'] ?? '' ),
			'ip'            => (string) ( $item['ip'] ?? IpHelper::get_client_ip() ),
			'useragent'     => (string) ( $item['useragent'] ?? self::get_client_useragent() ),
			'post_id'       => (int) ( $item['post_id'] ?? 0 ),
		];

		$payload['host'] = (string) ( $item['host'] ?? '' );

		if ( '' === $payload['host'] && '' !== $payload['url'] ) {
			$payload['host'] = DataHelper::parse_url( $payload['url'] );
		}

		/**
		 * Filters the normalized payload before the rules are applied.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $payload       The normalized payload.
		 * @param array  $item          The item as passed to the API.
		 * @param string $reaction_type The reaction type.
		 */
		return (array) apply_filters( 'antispam_bee_api_payload', $payload, $item, $reaction_type );
	}

	/**
	 * Get the user agent of the current request.
	 *
	 * @return string The user agent, or an empty string.
	 */
	private static function get_client_useragent(): string {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}
}
