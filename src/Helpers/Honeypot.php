<?php
/**
 * The Honeypot field.
 *
 * @package AntispamBee\Fields
 */

namespace AntispamBee\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Honeypot field.
 */
class Honeypot {
	/**
	 * Option that holds the secret the field names are built from.
	 *
	 * @var string
	 */
	public const SECRET_OPTION = 'antispam_bee_honeypot_secret';

	/**
	 * Option holding whether the last rendered comment form carries the honeypot.
	 *
	 * @var string
	 */
	public const INJECTION_STATE_OPTION = 'antispam_bee_honeypot_injection';

	/**
	 * Stored when the last rendered comment form carries the honeypot.
	 *
	 * @var string
	 */
	public const INJECTION_STATE_INJECTED = 'injected';

	/**
	 * Stored when the honeypot could not be placed into the last rendered form.
	 *
	 * @var string
	 */
	public const INJECTION_STATE_FAILED = 'failed';

	/**
	 * Record whether the last rendered comment form carries the honeypot.
	 *
	 * A comment submitted without the secret field is rejected as an invalid
	 * request, which is only fair if the form it came from had that field.
	 * `inject()` cannot always place it: the comment field may be missing, carry
	 * a different id, not be a textarea, or the markup may not match — and a form
	 * built without `comment_form()` never reaches it at all. Only the server that
	 * rendered the form knows which case applies, so the outcome is kept here for
	 * `Rules\Honeypot::precheck()` to consult. A bot posting to
	 * `wp-comments-post.php` directly has no way to change it.
	 *
	 * The option is written only when the outcome changes, so rendering the same
	 * form again costs no database write.
	 *
	 * @param bool $injected Whether the honeypot was placed into the form.
	 *
	 * @return void
	 */
	public static function record_injection( bool $injected ): void {
		$state = $injected ? self::INJECTION_STATE_INJECTED : self::INJECTION_STATE_FAILED;

		if ( get_option( self::INJECTION_STATE_OPTION ) === $state ) {
			return;
		}

		update_option( self::INJECTION_STATE_OPTION, $state );
	}

	/**
	 * Whether the last rendered comment form was seen to carry the honeypot.
	 *
	 * False both when the injection failed and when no form has been rendered
	 * through the plugin yet, for example because the theme builds its own.
	 *
	 * @return bool True if the honeypot was placed into the last rendered form.
	 */
	public static function injection_observed(): bool {
		return self::INJECTION_STATE_INJECTED === get_option( self::INJECTION_STATE_OPTION );
	}

	/**
	 * Inject the honeypot field.
	 *
	 * @param string|mixed          $markup  The field markup. Anything on the
	 *                                       `comment_form_field_comment` filter can
	 *                                       reach this, so the type is not enforced.
	 * @param array<string, string> $options {
	 *                           The field options.
	 *
	 * @type string  $form_id    The form id.
	 * @type string  $form_name  The form name.
	 * @type string  $field_type The field type.
	 * @type string  $field_id   The field id.
	 * @type string  $field_name The field name. When given, a textarea carrying it is
	 *                           used if no element has the field id — as in forms not
	 *                           built with `comment_form()`.
	 *                           }
	 *
	 * @return string The markup with the injected honeypot field.
	 */
	public static function inject( $markup, array $options ): string {
		/*
		 * The value comes from `comment_form_field_comment`, so anything on that
		 * filter can hand this an empty string or null: a theme that renders the
		 * comment textarea itself, or a callback that forgets to return. Neither
		 * is a problem on its own — core would simply render nothing — but
		 * `DOMDocument::loadHTML()` throws a `ValueError` on an empty string, and
		 * a non-nullable `string` parameter throws a `TypeError` on null. Either
		 * would fatal every page that renders a comment form, so nothing to parse
		 * means nothing to inject.
		 */
		if ( ! is_string( $markup ) || '' === trim( $markup ) ) {
			return is_string( $markup ) ? $markup : '';
		}

		$dom = new DOMDocument();
		// Malformed or HTML5-only markup must not bubble up as PHP warnings.
		$use_internal_errors = libxml_use_internal_errors( true );
		$dom->loadHTML( $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $use_internal_errors );

		$xpath     = new DOMXPath( $dom );
		$node_list = $xpath->query( '//*[@id="' . $options['field_id'] . '"]' );
		$input     = $node_list ? $node_list->item( 0 ) : null;
		if ( ! $input instanceof DOMElement && isset( $options['field_name'] ) ) {
			$node_list = $xpath->query( '//textarea[@name="' . $options['field_name'] . '"]' );
			$input     = $node_list ? $node_list->item( 0 ) : null;
		}
		if ( ! $input instanceof DOMElement ) {
			return $markup;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$id_attr   = $input->attributes->getNamedItem( 'id' );
		$name_attr = $input->attributes->getNamedItem( 'name' );
		if ( null === $name_attr || ( null === $id_attr && ! isset( $options['field_name'] ) ) ) {
			return $markup;
		}

		$input_type    = $input->nodeName;
		$honeypot_id   = null === $id_attr ? '' : $id_attr->textContent;
		$honeypot_name = $name_attr->textContent;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		/**
		 * Filter to change the inline styles of the honeypot field.
		 *
		 * This filter can also be used to use an empty value and load the
		 * styles from an external file if a strict CSP is used for the site.
		 *
		 * @see: https://wordpress.org/support/topic/honeypot-textarea-visible-with-strict-csp-header/
		 *
		 * @since 3.0.0
		 *
		 * @param string $honeypot_styles The inline styles for the honeypot.
		 */
		$honeypot_styles = apply_filters( 'antispam_bee_honeypot_styles', 'padding:0 !important;clip:rect(1px, 1px, 1px, 1px) !important;position:absolute !important;white-space:nowrap !important;height:1px !important;width:1px !important;overflow:hidden !important;' );

		/*
		 * The id and name come from the theme's markup and the styles from a
		 * filter, so none of them is trusted here — the sibling values a few lines
		 * down are escaped the same way.
		 */
		$attributes_string = sprintf(
			'%sname="%s" aria-hidden="true" aria-label="hp-comment" autocomplete="new-password" tabindex="-1" style="%s"',
			'' === $honeypot_id ? '' : 'id="' . esc_attr( $honeypot_id ) . '" ',
			esc_attr( $honeypot_name ),
			esc_attr( $honeypot_styles )
		);
		switch ( $input_type ) {
			case 'textarea':
				/*
				 * Quoted before substitution: the pattern uses the `/x` modifier, so
				 * an unescaped `#` in an id or name comments out the rest of that
				 * line, and characters such as `[`, `]`, `(`, `.` or `\` change what
				 * the pattern means. Both values come from the theme's markup. The
				 * failure is silent — the pattern still compiles and simply stops
				 * matching, so the field is left without a honeypot.
				 */
				$regex = str_replace(
					[ '{{HONEYPOT_ID}}', '{{HONEYPOT_NAME}}' ],
					// A field without an id can only be matched by the id-less alternative.
					[ '' === $honeypot_id ? '(?!)' : preg_quote( $honeypot_id, '/' ), preg_quote( $honeypot_name, '/' ) ],
					'/(?P<all>                                    (?# match the whole textarea tag )
						<textarea                                        (?# the opening of the textarea and some optional attributes )
						(                                                (?# match a id attribute followed by some optional ones and the name attribute )
							(?P<before1>[^>]*)
							(?P<id1>id=["\']?{{HONEYPOT_ID}}["\']?)
							(?P<between1>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							|                                            (?# match same as before, but with the name attribute before the id attribute )
							(?P<before2>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							(?P<between2>[^>]*)
							(?P<id2>id=["\']?{{HONEYPOT_ID}}["\']?)
							|                                            (?# match same as before, but with no id attribute )
							(?P<before3>[^>]*)
							name=["\']?{{HONEYPOT_NAME}}["\']?
							(?P<between3>[^>]*)
						)
						(?P<after>[^>]*)                                 (?# match any additional optional attributes )
						>                                                (?# the closing of the textarea opening tag )
						(?s)(?P<content>.*?)                             (?# any textarea content )
						<\/textarea>                                     (?# the closing textarea tag )
					)/x'
				);

				$markup = preg_replace_callback(
					$regex,
					function ( array $matches ) use ( $honeypot_id, $attributes_string ) {
						$output = '<textarea autocomplete="new-password" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];

						$id_script = '';
						if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
							$output .= 'id="' . esc_attr( self::get_secret_id_for_post() ) . '" ';
							if ( ! self::is_amp() ) {
								$id_script = sprintf(
									'<script data-noptimize>document.getElementById("%1$s").setAttribute( "id", "a%2$s" );document.getElementById("%3$s").setAttribute( "id", "%1$s" );</script>',
									$honeypot_id,
									esc_js( substr( md5( (string) time() ), 0, 31 ) ),
									esc_js( self::get_secret_id_for_post() )
								);
							}
						}

						$output .= ' name="' . esc_attr( self::get_secret_name_for_post() ) . '" ';
						$output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
						$output .= $matches['after'] . '>';
						$output .= $matches['content'];
						$output .= '</textarea><textarea ' . $attributes_string . '></textarea>';

						$output .= $id_script;

						return $output;
					},
					$markup
				) ?? $markup;
				break;
			case 'input':
				// The visible input gets the secret name so its real content is
				// not the honeypot bait. A hidden duplicate carrying the comment
				// name is appended right behind it, without an id so there are
				// not two elements with the same id on the page.
				$secret_name = self::get_secret_name_for_post();

				$quoted_id   = preg_quote( $honeypot_id, '/' );
				$quoted_name = preg_quote( $honeypot_name, '/' );

				// Rebuild only the matching input tag in the raw markup, so
				// single and double quoting and attribute order all work. The
				// lookaheads make sure this input has the comment id and name,
				// matched as complete values, not prefix of another one.
				$tag_re = '/<input\b(?=[^>]*\bid=("' . $quoted_id . '"|\'' . $quoted_id . '\'|' . $quoted_id . '(?=[\s\/>])))(?=[^>]*\bname=("' . $quoted_name . '"|\'' . $quoted_name . '\'|' . $quoted_name . '(?=[\s\/>])))[^>]*\/?>/';

				$honeypot_attrs = sprintf(
					'name="%1$s" aria-hidden="true" aria-label="hp-comment" autocomplete="new-password" tabindex="-1" style="%2$s"',
					esc_attr( $honeypot_name ),
					esc_attr( $honeypot_styles )
				);

				$markup = preg_replace_callback(
					$tag_re,
					function ( array $matches ) use ( $secret_name, $honeypot_attrs ) {
						// Swap the name for the secret, keep everything else. The
						// leading \s stops \b from matching the name inside a
						// data-name attribute placed before the real one.
						$rewritten = preg_replace(
							'/(^|\s)name=["\']?[^"\'>\s]+["\']?/',
							'$1name="' . esc_attr( $secret_name ) . '"',
							$matches[0],
							1
						);

						return $rewritten . '<input ' . $honeypot_attrs . '>';
					},
					$markup,
					1
				) ?? $markup;
				break;
			default:
				break;
		}

		return $markup;
	}

	/**
	 * Return the secret of a post used in the textarea id attribute.
	 *
	 * @return string The secret used in the textarea id attribute.
	 */
	public static function get_secret_id_for_post(): string {
		return self::get_secret();
	}

	/**
	 * Get the secret the honeypot field names are built from.
	 *
	 * The names are rendered into the comment form, so a page cache can serve a
	 * form long after it was generated. If the names have changed by the time that
	 * form is submitted, the honeypot and invalid-request rules see a field they
	 * did not create and treat a genuine comment as spam. Deriving the names from
	 * the `wp-config.php` salts made that happen whenever those were rotated or a
	 * site was migrated, so the secret is stored once and then reused.
	 *
	 * What is stored is the finished secret, not the salt it came from: the secret
	 * is in the markup of every comment form already, so keeping it in the options
	 * table discloses nothing, whereas storing the salt would copy a value out of
	 * `wp-config.php` into the database.
	 *
	 * @return string The secret.
	 */
	private static function get_secret(): string {
		$secret = get_option( self::SECRET_OPTION );

		if ( ! is_string( $secret ) || '' === $secret ) {
			$secret = self::store_secret();
		}

		/**
		 * Filters the secret the honeypot field names are built from.
		 *
		 * The stored secret never changes on its own, which is what keeps cached
		 * comment forms valid. Filtering it renames every field, so a form already
		 * sitting in a page cache stops matching until that cache is cleared.
		 *
		 * @since 3.0.0
		 *
		 * @param string $secret The stored secret.
		 */
		$secret = (string) apply_filters( 'antispam_bee_honeypot_secret', $secret );

		// The secret is used as an HTML id, which may not start with a digit, so the
		// invariant is re-applied in case a filter returned a value that breaks it.
		return self::ensure_secret_starts_with_letter( $secret );
	}

	/**
	 * Create and store the secret.
	 *
	 * @return string The stored secret.
	 */
	private static function store_secret(): string {
		$secret = self::derive_secret();

		// A concurrent request may have stored one first; that one wins.
		if ( ! add_option( self::SECRET_OPTION, $secret ) ) {
			$stored = get_option( self::SECRET_OPTION );

			if ( is_string( $stored ) && '' !== $stored ) {
				return $stored;
			}
		}

		return $secret;
	}

	/**
	 * Derive the secret to store.
	 *
	 * Deriving it from the configured salt reproduces the names the site is
	 * already serving, so storing the secret does not invalidate forms that are
	 * already in a page cache. {@see Salt::get()} only returns a configured salt
	 * when it looks generated, and falls back to the one WordPress manages
	 * otherwise — the case where the previous derivation was predictable anyway.
	 *
	 * @return string The derived secret.
	 */
	private static function derive_secret(): string {
		$salt = Salt::get();

		if ( '' === $salt ) {
			$salt = Salt::generate();
		}

		return self::ensure_secret_starts_with_letter(
			substr( sha1( md5( 'comment-id' . $salt ) ), 0, 10 )
		);
	}

	/**
	 * Ensure that the secret starts with a letter.
	 *
	 * @param string $secret The secret.
	 *
	 * @return string The secret starting with a letter.
	 */
	public static function ensure_secret_starts_with_letter( string $secret ): string {
		$first_char = substr( $secret, 0, 1 );
		if ( is_numeric( $first_char ) ) {
			return chr( ( (int) $first_char % 10 ) + 97 ) . substr( $secret, 1 );
		}

		return $secret;
	}

	/**
	 * Test if we are on an AMP site.
	 *
	 * Starting with v2.0, amp_is_request() is the preferred method to check,
	 * but we fall back to the then deprecated is_amp_endpoint() as needed.
	 *
	 * @return bool Whether we are on an AMP site.
	 */
	private static function is_amp(): bool {
		return ( function_exists( 'amp_is_request' ) && amp_is_request() ) || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );
	}

	/**
	 * Return the secret of a post used in the textarea name attribute.
	 *
	 * @return string The secret used in the textarea name attribute.
	 */
	public static function get_secret_name_for_post(): string {
		return self::get_secret();
	}
}
