<?php
/**
 * UpdateSpamLog Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

/**
 * Post-processor that is responsible for updating the spam log file.
 */
class UpdateSpamLog extends Base {

	/**
	 * Post-processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-update-spam-log';

	/**
	 * Process an item.
	 * Append a line to the spam log file.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
	 */
	public static function process( array $item ): array {
		// The IP is the only field a Fail2Ban filter really needs, everything else is contextual.
		$ip = self::get_ip( $item );

		if ( '' === $ip ) {
			$item['asb_post_processors_failed'][] = self::get_slug();

			return $item;
		}

		if (
			! defined( 'ANTISPAM_BEE_LOG_FILE' )
			|| ! ANTISPAM_BEE_LOG_FILE
			|| validate_file( ANTISPAM_BEE_LOG_FILE ) !== 0
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- WP_Filesystem cannot perform an atomic FILE_APPEND | LOCK_EX write to the log file.
			|| ! is_writable( ANTISPAM_BEE_LOG_FILE )
		) {
			return $item;
		}

		$entry = self::get_entry( $item, $ip );

		/**
		 * Filter the line that is appended to the spam log file.
		 *
		 * Return an empty string to skip the item, for example to log honeypot hits only.
		 * The line is written as-is, followed by a single newline; do not add one yourself.
		 *
		 * @since 3.0.0
		 *
		 * @param string $entry The log entry, without a trailing newline.
		 * @param array  $item  The item that was marked as spam.
		 */
		$entry = apply_filters( 'antispam_bee_spam_log_entry', $entry, $item );

		if ( ! is_string( $entry ) ) {
			return $item;
		}

		// A filtered entry must stay a single line, otherwise it breaks every log parser reading the file.
		$single_line = preg_replace( '/\s+/', ' ', $entry );
		$entry       = null === $single_line ? '' : trim( $single_line );

		if ( '' === $entry ) {
			return $item;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem cannot perform an atomic FILE_APPEND | LOCK_EX write to the log file.
		file_put_contents(
			ANTISPAM_BEE_LOG_FILE,
			$entry . PHP_EOL,
			FILE_APPEND | LOCK_EX
		);

		return $item;
	}

	/**
	 * Build the log entry for an item.
	 *
	 * Comments keep the wording the log has used since 2.5.7, so existing Fail2Ban
	 * filters keep matching. Any other reaction is logged with its type instead of
	 * the post reference it does not have.
	 *
	 * @param array<string, mixed> $item Item that was marked as spam.
	 * @param string               $ip   IP address the item was submitted from.
	 *
	 * @return string The log entry, without a trailing newline.
	 */
	private static function get_entry( array $item, string $ip ): string {
		if ( isset( $item['comment_post_ID'] ) ) {
			$subject = sprintf( 'comment for post=%d', $item['comment_post_ID'] );
		} else {
			$subject = self::sanitize_token( $item['reaction_type'] ?? 'item' );
		}

		$reasons = '';
		if ( ! empty( $item['asb_reasons'] ) ) {
			$reasons = sprintf(
				' (%s)',
				implode( ',', array_map( [ self::class, 'sanitize_token' ], (array) $item['asb_reasons'] ) )
			);
		}

		return sprintf(
			'%s %s from host=%s marked as spam%s',
			current_time( 'mysql' ),
			$subject,
			$ip,
			$reasons
		);
	}

	/**
	 * Get the IP address an item was submitted from.
	 *
	 * Comments and linkbacks carry it as `comment_author_IP`. Items that reach the
	 * post-processors through {@see \AntispamBee\Api\SpamCheck::post_process()} are
	 * passed on unchanged, so they may instead use `ip`, the attribute name the
	 * spam check itself expects.
	 *
	 * @param array<string, mixed> $item Item that was marked as spam.
	 *
	 * @return string The IP address, or an empty string if the item has none.
	 */
	private static function get_ip( array $item ): string {
		$ip = $item['comment_author_IP'] ?? $item['ip'] ?? '';

		if ( ! is_string( $ip ) ) {
			return '';
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Reduce a slug to characters that are safe to write into a log line.
	 *
	 * Reaction types and rule slugs can come from third-party integrations, which
	 * are not guaranteed to stick to slug characters.
	 *
	 * @param mixed $token Slug to sanitize.
	 *
	 * @return string The sanitized slug.
	 */
	private static function sanitize_token( $token ): string {
		$sanitized = preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $token );

		return empty( $sanitized ) ? 'unknown' : $sanitized;
	}
}
