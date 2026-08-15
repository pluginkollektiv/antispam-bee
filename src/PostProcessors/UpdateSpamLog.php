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
	 * Space-separated `key=value` pairs, the shape `filter.d/dovecot.conf` already
	 * matches on a great many servers, so it is not exotic in Fail2Ban terms. Every
	 * value is space-free by construction, which is what makes the line parseable
	 * without quoting. A field that has no value for a reaction is written as `-`
	 * rather than omitted, so the field set is the same on every line.
	 *
	 * The timestamp is not one of the fields: it has no key, and Fail2Ban looks for
	 * it near the start of the line to apply `findtime`, so it must not be movable.
	 *
	 * @param array<string, mixed> $item Item that was marked as spam.
	 * @param string               $ip   IP address the item was submitted from.
	 *
	 * @return string The log entry, without a trailing newline.
	 */
	private static function get_entry( array $item, string $ip ): string {
		$reasons = '';
		if ( ! empty( $item['asb_reasons'] ) ) {
			$reasons = implode( ',', array_map( [ self::class, 'sanitize_token' ], (array) $item['asb_reasons'] ) );
		}

		$fields = [
			'ip'      => $ip,
			'type'    => self::sanitize_token( $item['reaction_type'] ?? '' ),
			'post'    => isset( $item['comment_post_ID'] ) ? (int) $item['comment_post_ID'] : '',
			'reasons' => $reasons,
		];

		/**
		 * Filter the fields that make up a spam log line.
		 *
		 * Keys become the `key=` part and are written in the order of the array, so
		 * appending to it adds a field at the end of the line. Keys are reduced to
		 * `[a-zA-Z0-9_-]` and a key left empty by that is dropped; values have their
		 * whitespace replaced and an empty value is written as `-`, so a filter
		 * cannot break the shape of the line. An array value is joined with commas.
		 *
		 * The timestamp is not part of this array — it has no key and has to stay at
		 * the start of the line for Fail2Ban's date detection.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $fields The fields of the log line.
		 * @param array<string, mixed> $item   The item that was marked as spam.
		 */
		$fields = (array) apply_filters( 'antispam_bee_spam_log_fields', $fields, $item );

		// The documented Fail2Ban filter matches `ip=<HOST>`; a line without it would silently stop being banned on.
		if ( ! isset( $fields['ip'] ) ) {
			$fields = array_merge( [ 'ip' => $ip ], $fields );
		}

		$pairs = [];

		foreach ( $fields as $key => $value ) {
			$key = self::sanitize_key( $key );

			if ( '' === $key ) {
				continue;
			}

			$pairs[] = $key . '=' . self::sanitize_value( $value );
		}

		if ( [] === $pairs ) {
			return self::get_timestamp();
		}

		return self::get_timestamp() . ' ' . implode( ' ', $pairs );
	}

	/**
	 * Reduce a field name to characters that are safe in a `key=value` line.
	 *
	 * @param mixed $key Field name to sanitize.
	 *
	 * @return string The sanitized field name, empty if nothing usable was left.
	 */
	private static function sanitize_key( $key ): string {
		return (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key );
	}

	/**
	 * Make a field value safe to write into a `key=value` line.
	 *
	 * Only whitespace and control characters actually break the format, so unlike
	 * {@see self::sanitize_token()} this keeps punctuation: a filter may well add a
	 * value that needs a dot or a colon, and stripping those would corrupt it
	 * silently rather than protect anything.
	 *
	 * @param mixed $value Field value to sanitize.
	 *
	 * @return string The sanitized value, or `-` if it is empty.
	 */
	private static function sanitize_value( $value ): string {
		if ( is_array( $value ) ) {
			$value = implode( ',', array_map( 'strval', $value ) );
		}

		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}

		// Whitespace first: a newline is also a control character, and deleting it
		// outright would run two words together instead of keeping them apart.
		$value = (string) preg_replace( '/\s+/', '_', trim( (string) $value ) );
		$value = (string) preg_replace( '/[[:cntrl:]]/', '', $value );

		return '' === $value ? '-' : $value;
	}

	/**
	 * Get the current time as an ISO 8601 timestamp with a UTC offset.
	 *
	 * `current_time( 'mysql' )` writes WordPress-local time without an offset, so on
	 * a site whose timezone differs from the server's, Fail2Ban reads entries as
	 * outside `findtime` and silently never bans. The offset is derived from the
	 * difference between local and UTC time at this very instant rather than from
	 * the `gmt_offset` option, so it stays correct across daylight saving changes
	 * and cannot disagree with the wall clock it is appended to.
	 *
	 * @return string The timestamp, for example `2026-01-15T10:23:45+01:00`.
	 */
	private static function get_timestamp(): string {
		$local = (string) current_time( 'mysql' );
		$utc   = (string) current_time( 'mysql', true );

		$offset = strtotime( $local ) - strtotime( $utc );

		$sign    = $offset < 0 ? '-' : '+';
		$offset  = abs( $offset );
		$hours   = (int) floor( $offset / 3600 );
		$minutes = (int) floor( ( $offset % 3600 ) / 60 );

		return sprintf(
			'%s%s%02d:%02d',
			str_replace( ' ', 'T', $local ),
			$sign,
			$hours,
			$minutes
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
