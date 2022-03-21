<?php

namespace AntispamBee\PostProcessors;

use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Helpers\LangHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\PostProcessor;
use AntispamBee\Helpers\Settings;

class SendEmail implements PostProcessor, Controllable {

	use IsActive;
	use InitPostProcessor;

	public static function process( $item ) {
		if ( isset( $item['asb_marked_as_delete'] ) && $item['asb_marked_as_delete'] === true ) {
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

				$subject = sprintf(
					'[%s] %s',
					stripslashes_deep(
					// phpcs:ignore PHPCompatibility.ParameterValues.NewHTMLEntitiesEncodingDefault.NotSet
						html_entity_decode(
							get_bloginfo( 'name' ),
							ENT_QUOTES
						)
					),
					esc_html__( 'Comment marked as spam', 'antispam-bee' )
				);

				// Content.
				$content = strip_tags( stripslashes( $comment['comment_content'] ) );
				if ( ! $content ) {
					$content = sprintf(
						'-- %s --',
						esc_html__( 'Content removed by Antispam Bee', 'antispam-bee' )
					);
				}

				// Prepare Comment Type.
				$comment_name = ItemTypeHelper::get_type_name( $item['comment_type'] );

				// Body.
				$body = sprintf(
					"%s \"%s\"\r\n\r\n",
					esc_html__( 'New spam comment on your post', 'antispam-bee' ),
					strip_tags( $post->post_title )
				) . sprintf(
					"%s: %s\r\n",
					esc_html__( 'Author', 'antispam-bee' ),
					( empty( $comment['comment_author'] ) ? '' : strip_tags( $comment['comment_author'] ) )
				) . sprintf(
					"URL: %s\r\n",
					// empty check exists.
							esc_url( $comment['comment_author_url'] )
				) . sprintf(
					"%s: %s\r\n",
					esc_html__( 'Type', 'antispam-bee' ),
					esc_html( $comment_name )
				) . sprintf(
					"Whois: http://whois.arin.net/rest/ip/%s\r\n",
					$comment['comment_author_IP']
				) . sprintf(
					"%s: %s\r\n\r\n",
					esc_html__( 'Spam Reason', 'antispam-bee' ),
					esc_html( implode( ',', $item['asb_reasons'] ) )
				) . sprintf(
					"%s\r\n\r\n\r\n",
					$content
				) . (
						EMPTY_TRASH_DAYS ? (
						sprintf(
							"%s: %s\r\n",
							esc_html__( 'Trash it', 'antispam-bee' ),
							admin_url( 'comment.php?action=trash&c=' . $id )
						)
						) : (
						sprintf(
							"%s: %s\r\n",
							esc_html__( 'Delete it', 'antispam-bee' ),
							admin_url( 'comment.php?action=delete&c=' . $id )
						)
						)
						) . sprintf(
							"%s: %s\r\n",
							esc_html__( 'Approve it', 'antispam-bee' ),
							admin_url( 'comment.php?action=approve&c=' . $id )
						) . sprintf(
							"%s: %s\r\n\r\n",
							esc_html__( 'Spam list', 'antispam-bee' ),
							admin_url( 'edit-comments.php?comment_status=spam' )
						) . sprintf(
							"%s\r\n%s\r\n",
							esc_html__( 'Notify message by Antispam Bee', 'antispam-bee' ),
							esc_html__( 'https://antispambee.pluginkollektiv.org/', 'antispam-bee' )
						);

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

	public static function get_slug() {
		return 'asb-send-email';
	}

	public static function get_supported_types() {
		return [ ItemTypeHelper::COMMENT_TYPE, ItemTypeHelper::TRACKBACK_TYPE ];
	}

	public static function get_label() {
		return __( 'Spam-Notification by email', 'antispam-bee' );
	}

	public static function get_description() {
		return __( 'Notify admins by e-mail about incoming spam', 'antispam-bee' );
	}

	public static function get_options() {
		return null;
	}

	public static function marks_as_delete() {
		return false;
	}
}

