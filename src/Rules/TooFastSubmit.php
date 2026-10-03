<?php
/**
 * Too Fast Submit Rule.
 *
 * @package AntispamBee\Rules
 */

namespace AntispamBee\Rules;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Interfaces\SpamReason;

/**
 * Rule that is responsible for checking that at least a certain
 * timespan has passed so that the comment won't be marked as invalid.
 */
class TooFastSubmit extends ControllableBase implements SpamReason {

	/**
	 * Rule slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-too-fast-submit';

	/**
	 * Only comments are supported.
	 *
	 * @var array<int, string>
	 */
	protected static $supported_types = [ ContentTypeHelper::COMMENT_TYPE ];

	/**
	 * Initialize the rule.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'antispam_bee_rules', [ __CLASS__, 'add_rule' ] );

		add_filter( 'comment_form_field_comment', [ self::class, 'inject_time_field' ] );
	}

	/**
	 * Inject the submission-time field into the comment form.
	 *
	 * @param string $field_markup Markup of the comment field.
	 *
	 * @return string The markup, with the time field injected.
	 */
	public static function inject_time_field( $field_markup ) {
		if ( ! self::is_active( ContentTypeHelper::COMMENT_TYPE ) ) {
			return $field_markup;
		}

		/*
		 * ab_init_time is the server's own clock and is left untouched by the script
		 * below: it is the fallback for a client that does not run JavaScript, and
		 * behind a page cache it is always old enough to clear the limit, so the rule
		 * lets the reaction pass rather than rejecting a visitor whose browser it
		 * cannot measure.
		 *
		 * ab_elapsed_time is filled in by the script at submit time, as the number of
		 * seconds between render and submission measured entirely by the client's own
		 * clock. Comparing it to the time limit below never mixes the client's and
		 * server's clocks, so clock skew between them - in either direction - cannot
		 * affect the result, unlike a comparison against an absolute timestamp from
		 * either clock.
		 *
		 * performance.now(), not Date.now(), measures it: Date.now() is a wall clock
		 * and can jump backwards mid-session if the OS corrects it (NTP sync on a
		 * device that booted with the wrong time), which would otherwise yield a
		 * negative duration. performance.now() is monotonic - unaffected by wall-clock
		 * adjustments - precisely because it is not tied to wall-clock time at all.
		 */
		$unique_id = uniqid( 'antispam-bee-time-' );
		$script    = sprintf(
			'<script>(function() {
				var start = performance.now(),
					elapsedField = document.querySelector(\'input[data-unique-id="%s"]\'),
					form = elapsedField ? elapsedField.closest(\'form\') : null;

				if (form) {
					form.addEventListener(\'submit\', function() {
						elapsedField.value = Math.floor((performance.now() - start) / 1000);
					});
				}
			}());</script>',
			$unique_id
		);

		return $field_markup . sprintf(
			'<input type="hidden" name="ab_init_time" value="%d" /><input type="hidden" name="ab_elapsed_time" data-unique-id="%s" value="" />%s',
			time(),
			$unique_id,
			$script
		);
	}

	/**
	 * Verify an item.
	 *
	 * Test for time between page initialization and reaction.
	 *
	 * Consumes no payload attributes; reads the request (`$_POST`) directly.
	 *
	 * @param array<string, mixed> $item Normalized payload to verify.
	 *
	 * @return int Numeric result.
	 */
	public static function verify( array $item ): int {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Everybody can Post.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['ab_elapsed_time'] ) && '' !== wp_unslash( $_POST['ab_elapsed_time'] ) ) {
			// Measured entirely by the client's own clock (two performance.now() reads,
			// differenced), so client/server clock skew cannot affect it either way -
			// unlike comparing an absolute timestamp from one clock against the other.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$dwell_time = (int) wp_unslash( $_POST['ab_elapsed_time'] );

			// A negative value is impossible for a correctly functioning client (the
			// script uses performance.now(), which cannot go backwards) and is a sign
			// of a forged or malfunctioning value, not a fast submission - fail open
			// rather than let it reach the comparison below, where it would otherwise
			// always classify as spam regardless of magnitude.
			if ( $dwell_time < 0 ) {
				return 0;
			}
		} else {
			if ( ! isset( $_POST['ab_init_time'] ) ) {
				return 0;
			}
			$init_time = (int) $_POST['ab_init_time'];
			if ( 0 === $init_time ) {
				return 0;
			}

			$dwell_time = time() - $init_time;

			/*
			 * No ab_elapsed_time means a client that did not run the script (or a form
			 * cached from before it existed): ab_init_time is the server's own clock, so
			 * comparing it to time() below is normally skew-free too. The one exception
			 * is a page cached from before this fallback existed, whose script rewrote
			 * ab_init_time to the client's clock - a negative computed dwell time is a
			 * sign of that stale cached page (or any other clock mismatch between render
			 * and submission), not a fast submission, so fail open instead of
			 * misclassifying it. Mirrors the guard on the ab_elapsed_time branch above.
			 */
			if ( $dwell_time < 0 ) {
				return 0;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/**
		 * Filters the minimum time (in seconds) a form has to stay open before submission.
		 *
		 * A reaction that is submitted faster than this limit after the form was
		 * rendered is considered spam, as it is likely sent by an automated bot.
		 *
		 * @since 3.0.0
		 *
		 * @param int $action_time_limit The minimum number of seconds. Default 5.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$action_time_limit = (int) apply_filters( 'antispam_bee_action_time_limit', 5 );

		return (int) ( $dwell_time < $action_time_limit );
	}

	/**
	 * Get the rule name.
	 *
	 * @return string The rule name.
	 */
	public static function get_name(): string {
		return __( 'Comment time', 'antispam-bee' );
	}

	/**
	 * Get the rule label.
	 *
	 * @return string|null The rule label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Consider the comment time', 'antispam-bee' );
	}

	/**
	 * Get the rule description.
	 *
	 * The time the form was opened is measured client-side, so a page cache does not
	 * stop the rule from working. Without JavaScript the measurement falls back to
	 * the rendered timestamp, which a cache makes useless.
	 *
	 * @return string|null The rule description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'Works with page caching only if JavaScript is enabled', 'antispam-bee' );
	}

	/**
	 * Get a human-readable spam reason.
	 *
	 * @return string The human-readable spam reason.
	 */
	public static function get_reason_text(): string {
		return _x( 'Created too quickly', 'spam-reason-text', 'antispam-bee' );
	}
}
