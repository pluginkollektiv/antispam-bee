<?php
/**
 * SendEmail Post-Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\SpamReasonTextHelper;
use WP_Post;

/**
 * Post-processor that is responsible for sending emails to the user.
 */
class SendEmail extends ControllableBase {

	/**
	 * Post-processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-send-email';

	/**
	 * Process an item.
	 * Generate an email and send it.
	 *
	 * @param array<string, mixed> $item Item to process.
	 *
	 * @return array<string, mixed> Processed item.
	 */
	public static function process( array $item ): array {
		if ( isset( $item['asb_marked_as_delete'] ) && true === $item['asb_marked_as_delete'] ) {
			return $item;
		}

		// The notification is built from the stored comment, so the item has to become one.
		if ( ! isset( $item['comment_post_ID'] ) ) {
			$item['asb_post_processors_failed'][] = self::get_slug();

			return $item;
		}

		add_action(
			'comment_post',
			function ( $id ) use ( $item ) {
				$comment = get_comment( $id, ARRAY_A );

				if ( empty( $comment ) ) {
					return;
				}

				$post = get_post( $comment['comment_post_ID'] );
				if ( ! $post ) {
					return;
				}

				$subject = self::get_subject();

				// Body.
				$body = self::get_body( $post, $comment, $item );

				wp_mail(
				/**
				 * Filters the recipients of the spam notification email.
				 *
				 * By default the notification is sent to the site’s admin email
				 * address. Use this filter to send it to additional or different
				 * recipients.
				 *
				 * @since 2.8.0
				 *
				 * @param array $recipients The list of recipient email addresses.
				 */
					apply_filters(
						'antispam_bee_notification_recipients',
						[ get_bloginfo( 'admin_email' ) ]
					),
					/**
					 * Filters the subject of the spam notification email.
					 *
					 * @since 2.5.7
					 *
					 * @param string $subject The email subject line.
					 */
					apply_filters(
						'antispam_bee_notification_subject',
						$subject
					),
					$body
				);
			}
		);

		return $item;
	}

	/**
	 * Generate email subject.
	 *
	 * @return string The email subject.
	 */
	private static function get_subject(): string {
		return sprintf(
			'[%s] %s',
			stripslashes(
			// phpcs:ignore PHPCompatibility.ParameterValues.NewHTMLEntitiesEncodingDefault.NotSet
				html_entity_decode(
					get_bloginfo( 'name' ),
					ENT_QUOTES
				)
			),
			esc_html__( 'Comment marked as spam', 'antispam-bee' )
		);
	}

	/**
	 * Generate email body.
	 *
	 * @param WP_Post              $post    The post.
	 * @param array<string, mixed> $comment The comment.
	 * @param array<string, mixed> $item    Processed item.
	 *
	 * @return string The email body.
	 */
	protected static function get_body( WP_Post $post, array $comment, array $item ): string {
		$template_content = self::get_body_template();

		$content       = self::get_content( $comment );
		$reaction_type = ContentTypeHelper::get_reaction_type_name( $item['reaction_type'] );

		$spam_reasons = SpamReasonTextHelper::get_texts_by_slugs( $item['asb_reasons'] );

		$replacements = [
			'{{post_title}}'         => wp_strip_all_tags( $post->post_title ),
			'{{comment_author}}'     => empty( $comment['comment_author'] ) ? '' : wp_strip_all_tags( $comment['comment_author'] ),
			'{{comment_author_url}}' => esc_url( $comment['comment_author_url'] ),
			'{{reaction_type}}'      => esc_html( $reaction_type ),
			'{{comment_author_IP}}'  => $comment['comment_author_IP'],
			'{{spam_reasons}}'       => esc_html( implode( ', ', $spam_reasons ) ),
			'{{content}}'            => $content,
			'{{comment_id}}'         => $comment['comment_ID'],
		];

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template_content );
	}

	/**
	 * Get the template for the email body.
	 *
	 * @return string The email body template.
	 */
	private static function get_body_template(): string {
		$new_spam_comment = sprintf( /* translators: s=post title. */
			esc_html__( 'New spam comment on your post “%s”,', 'antispam-bee' ),
			'{{post_title}}'
		);
		$author           = esc_html__( 'Author', 'antispam-bee' );
		$url              = esc_html__( 'URL', 'antispam-bee' );
		$type             = esc_html__( 'Type', 'antispam-bee' );
		$spam_reasons     = esc_html__( 'Spam Reasons', 'antispam-bee' );

		$remove_label = esc_html__( 'Delete it', 'antispam-bee' );
		$remove_url   = admin_url( 'comment.php?action=delete&c={{comment_id}}' );
		if ( EMPTY_TRASH_DAYS ) {
			$remove_label = esc_html__( 'Trash it', 'antispam-bee' );
			$remove_url   = admin_url( 'comment.php?action=trash&c={{comment_id}}' );
		}
		$approve_label = esc_html__( 'Approve it', 'antispam-bee' );
		$approve_url   = admin_url( 'comment.php?action=approve&c={{comment_id}}' );

		$spam_list_label = esc_html__( 'Spam list', 'antispam-bee' );
		$spam_list_url   = admin_url( 'edit-comments.php?comment_status=spam' );

		$asb_message = esc_html__( 'Notify message by Antispam Bee', 'antispam-bee' );
		$asb_url     = esc_html__( 'https://antispambee.pluginkollektiv.org/', 'antispam-bee' );

		// Heredoc syntax is not allowed by the WordPress.org plugin review, so the
		// template is assembled line by line. The trailing empty element keeps the
		// closing newline the template has always ended with.
		$body = implode(
			PHP_EOL,
			[
				$new_spam_comment,
				'',
				"$author: {{comment_author}}",
				"$url: {{comment_author_url}}",
				"$type: {{reaction_type}}",
				'Whois: https://whois.arin.net/rest/ip/{{comment_author_IP}}',
				"$spam_reasons: {{spam_reasons}}",
				'',
				'{{content}}',
				'',
				'',
				"$remove_label: $remove_url",
				'',
				"$approve_label: $approve_url",
				'',
				"$spam_list_label: $spam_list_url",
				'',
				$asb_message,
				$asb_url,
				'',
			]
		);

		return str_replace( PHP_EOL, "\r\n", $body );
	}

	/**
	 * Extract content from comment.
	 *
	 * @param array<string, mixed> $comment The comment.
	 *
	 * @return string The comment content.
	 */
	private static function get_content( array $comment ): string {
		$content = wp_strip_all_tags( stripslashes( $comment['comment_content'] ) );

		if ( $content ) {
			return $content;
		}

		return sprintf( '-- %s --', esc_html__( 'Content removed by Antispam Bee', 'antispam-bee' ) );
	}

	/**
	 * Get the element name.
	 *
	 * @return string The name.
	 */
	public static function get_name(): string {
		return __( 'Send email', 'antispam-bee' );
	}

	/**
	 * Get the element label (optional).
	 *
	 * @return string|null The label, or null.
	 */
	public static function get_label(): ?string {
		return __( 'Spam-Notification by email', 'antispam-bee' );
	}

	/**
	 * Get the element description (optional).
	 *
	 * @return string|null The description, or null.
	 */
	public static function get_description(): ?string {
		return __( 'Notify admins by e-mail about incoming spam', 'antispam-bee' );
	}
}
