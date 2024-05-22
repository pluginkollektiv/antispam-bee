<?php
/**
 * Send Email Post Processor.
 *
 * @package AntispamBee\PostProcessors
 */

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\SpamReasonTextHelper;

/**
 * Post processor that is responsible for sending emails to the user.
 */
class SendEmail extends ControllableBase {

	/**
	 * Post processor slug.
	 *
	 * @var string
	 */
	protected static $slug = 'asb-send-email';

	/**
	 * Process an item.
	 * Generate an email and send it.
	 *
	 * @param array $item Item to process.
	 * @return array Processed item.
	 */
	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && true === $item['asb_marked_as_delete'] ) {
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
				 * Filters the recipients of the spam notification.
				 *
				 * @param array The recipients array.
				 */
					apply_filters(
						'antispam_bee_notification_recipients',
						[ get_bloginfo( 'admin_email' ) ]
					),
					/**
					 * Filters the subject of the spam notification.
					 *
					 * @param string $subject subject line.
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
	 * Get element name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Send email', 'antispam-bee' );
	}

	/**
	 * Get element label (optional).
	 *
	 * @return string|null
	 */
	public static function get_label() {
		return __( 'Spam-Notification by email', 'antispam-bee' );
	}

	/**
	 * Get element description (optional).
	 *
	 * @return string|null
	 */
	public static function get_description() {
		return __( 'Notify admins by e-mail about incoming spam', 'antispam-bee' );
	}

	/**
	 * Generate email subject.
	 *
	 * @return string
	 */
	private static function get_subject() {
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
	 * Extract content from comment.
	 *
	 * @param array $comment The comment.
	 * @return string
	 */
	private static function get_content( $comment ) {
		$content = strip_tags( stripslashes( $comment['comment_content'] ) );

		if ( $content ) {
			return $content;
		}

		return sprintf( '-- %s --', esc_html__( 'Content removed by Antispam Bee', 'antispam-bee' ) );
	}

	/**
	 * Get template for email body.
	 *
	 * @return string
	 */
	private static function get_body_template() {
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

		$body = <<<EOF
$new_spam_comment

$author: {{comment_author}}
$url: {{comment_author_url}}
$type: {{reaction_type}}
Whois: http://whois.arin.net/rest/ip/{{comment_author_IP}}
$spam_reasons: {{spam_reasons}}

{{content}}


$remove_label: $remove_url

$approve_label: $approve_url

$spam_list_label: $spam_list_url

$asb_message
$asb_url

EOF;

		return str_replace( PHP_EOL, "\r\n", $body );
	}

	/**
	 * Generate email body.
	 *
	 * @param \WP_Post $post    The post.
	 * @param array    $comment The comment.
	 * @param array    $item    Processed item.
	 * @return string
	 */
	protected static function get_body( $post, $comment, $item ) {
		$template_content = self::get_body_template();

		$content       = self::get_content( $comment );
		$reaction_type = ContentTypeHelper::get_type_name( $item['reaction_type'] );

		$spam_reasons = SpamReasonTextHelper::get_texts_by_slugs( $item['asb_reasons'] );

		return str_replace(
			[
				'{{post_title}}',
				'{{comment_author}}',
				'{{comment_author_url}}',
				'{{reaction_type}}',
				'{{comment_author_IP}}',
				'{{spam_reasons}}',
				'{{content}}',
				'{{comment_id}}',
			],
			[
				strip_tags( $post->post_title ),
				( empty( $comment['comment_author'] ) ? '' : strip_tags( $comment['comment_author'] ) ),
				esc_url( $comment['comment_author_url'] ),
				esc_html( $reaction_type ),
				$comment['comment_author_IP'],
				esc_html( implode( ', ', $spam_reasons ) ),
				$content,
				$comment['comment_ID'],
			],
			$template_content
		);
	}
}
