<?php

/* Sicherheitsabfrage */
if ( ! class_exists('Antispam_Bee') ) {
	die();
}

/**
 * bbPress - Anitspam
 *
 * Description.
 *
 * @since 2.7.0
 */
class Antispam_Bee_bbPress extends Antispam_Bee {

	/**
	 * @var string temporary saving of spam reason
	 */
	static private $_reason = '';

	/**
	 * Init all actions
	 */
	public static function init() {

		if ( ! Antispam_Bee::get_option( 'bbpress_allowed' ) ) {
			return;
		}

		add_action(
			'bbp_get_the_content',
			array(
				__CLASS__,
				'bbpress_prepare_field'
			),
			99,
			2
		);
		add_filter(
			'bbp_new_topic_pre_content',
			array(
				__CLASS__,
				'post_pre_content'
			),
			1
		);
		add_filter(
			'bbp_new_reply_pre_content',
			array(
				__CLASS__,
				'post_pre_content'
			),
			1
		);
		add_filter(
			'bbp_new_topic_pre_insert',
			array(
				__CLASS__,
				'post_pre_insert'
			),
			100
		);
		add_filter(
			'bbp_new_reply_pre_insert',
		     array(
			     __CLASS__,
			     'post_pre_insert'
		     ),
			1
		);
		add_action(
			'bbp_new_topic',
		    array(
			    __CLASS__,
			    'new_topic'
		    ),
			100,
			1
		);
		add_action(
			'bbp_new_reply',
			array(
				__CLASS__,
				'new_reply'
			),
			100,
			2
		);
		add_filter(
			'bbp_admin_topics_column_headers',
			array(
				__CLASS__,
				'manage_columns'
			)
		);
		add_filter(
			'bbp_admin_replies_column_headers',
			array(
				__CLASS__,
				'manage_columns'
			)
		);
		add_filter(
			'manage_topic_posts_custom_column',
			array(
				__CLASS__,
				'manage_reason_column'
			),
			10,
			2
		);
		add_filter(
			'manage_reply_posts_custom_column',
			array(
				__CLASS__,
				'manage_reason_column'
			),
			10,
			2
		);

		if ( defined('DOING_CRON') ) {
			add_action(
				'antispam_bee_daily_cronjob',
				array(
					__CLASS__,
					'delete_old_spam'
				),
				11
			);
		}

	}

	/**
	 * Replaces the replay and topic content field of bbPress
	 *
	 * @param $output
	 * @param $args
	 *
	 * @return string
	 */
	public static function bbpress_prepare_field( $output, $args ) {

		if ( bbp_is_reply_edit() || bbp_is_topic_edit() ) {
			return $output;
		}

		if ( $args[ 'context' ] != 'topic' && $args[ 'context' ] != 'reply' ) {
			return $output;
		}

		// Parse arguments against default values
		$r = bbp_parse_args( $args, array(
			'context'           => 'topic',
			'textarea_rows'     => '12',
			'tabindex'          => bbp_get_tab_index(),
			'editor_class'      => 'bbp-the-content',
		), 'get_the_content' );

		/* Build init time field */
		if ( Antispam_Bee::get_option('time_check') ) {
			$init_time_field = sprintf(
				'<input type="hidden" name="ab_init_time" value="%d" />',
				time()
			);
		} else {
			$init_time_field = '';
		}

		$secret = Antispam_Bee::_get_secret( 'bbp_', '_content' );

		$new_output = '<textarea id="bbp_' . esc_attr( $r['context'] ) . '_content_css" class="' . esc_attr( $r['editor_class'] ) . '-css" name="bbp_' . esc_attr( $r['context'] ) . '_content" cols="60" rows="' . esc_attr( $r['textarea_rows'] ) . '" style="display:none;width:1px;height:1px;"></textarea>';
		$new_output .= $init_time_field;
		$new_output .= str_replace( array( 'name="bbp_' . esc_attr( $r['context'] ) . '_content"' ), array( 'name="' . esc_attr( $secret ) . '"' ), $output );

		return $new_output;
	}

	/**
	 * Check for Spam
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public static function post_pre_content( $content ) {

		$options = Antispam_Bee::get_options();
		$ip = bbp_current_author_ip();
		if ( bbp_is_anonymous() ) {
			$email  = !empty( $_POST['bbp_anonymous_email']   ) ? $_POST['bbp_anonymous_email']   : '';
			$author = !empty( $_POST['bbp_anonymous_name']    ) ? $_POST['bbp_anonymous_name']    : '';
			$url    = !empty( $_POST['bbp_anonymous_website'] ) ? $_POST['bbp_anonymous_website'] : '';
		} else {
			$user   = wp_get_current_user();
			$email  = $user->user_email;
			$author = $user->display_name;
			$url    = $user->user_url;
		}

		if ( ! empty( $content ) ) {
			self::$_reason = 'css';
		}

		//get real content
		$key =  Antispam_Bee::_get_secret( 'bbp_', '_content' );
		if ( current_filter() === 'bbp_new_topic_pre_content' ) {
			if ( isset( $_POST[ $key ] ) ) {
				$content = $_POST[ $key ];
			}
		}
		if ( current_filter() === 'bbp_new_reply_pre_content' ) {
			if ( isset( $_POST[ $key ] ) ) {
				$content = $_POST[ $key ];
			}
		}

		//no check for users that already commented or uses of the forum
		if ( $options['already_commented'] && Antispam_Bee::_is_approved_email( $email ) ) {
			return $content;
		}

		//no check on valid gravatar
		if ( $options['gravatar_check'] && Antispam_Bee::_has_valid_gravatar( $email ) ) {
			return $content;
		}

		if ( empty( self::$_reason ) && $options['time_check'] && Antispam_Bee::_is_shortest_time() ) {
			self::$_reason = 'time';
		}

		if ( empty( self::$_reason ) && $options['time_check'] && Antispam_Bee::_is_bbcode_spam( $content ) ) {
			self::$_reason = 'bbcode';
		}

		if ( empty( self::$_reason ) && $options['advanced_check'] && Antispam_Bee::_is_fake_ip( $ip ) ) {
			self::$_reason = 'server';
		}

		if ( empty( self::$_reason ) && $options['regexp_check'] && Antispam_Bee::_is_regexp_spam(
				array(
					'ip'	 => $ip,
					'host'	 => parse_url( $url, PHP_URL_HOST ),
					'body'	 => $content,
					'email'	 => $email,
					'author' => $author
				)
			) ) {
			self::$_reason = 'regexp';
		}

		if ( empty( self::$_reason ) && $options['spam_ip'] && Antispam_Bee::_is_db_spam( $ip, $url, $email ) ) {
			self::$_reason = 'localdb';
		}

		if ( empty( self::$_reason ) && $options['dnsbl_check'] && Antispam_Bee::_is_dnsbl_spam( $ip ) ) {
			self::$_reason = 'dnsbl';
		}

		//do on spam
		if ( ! empty( self::$_reason ) ) {
			Antispam_Bee::_update_spam_count();
			Antispam_Bee::_update_daily_stats();
			$ignore_reason = in_array( self::$_reason, (array) $options['ignore_reasons'] );
			if ( ! Antispam_Bee::get_option( 'flag_spam' ) && $ignore_reason ) {
				bbp_add_error( 'bbp_reply_content', sprintf( __( '<strong>Error</strong>: Antispam Bee (%s)!', 'antispam-bee' ), self::$_reason ) );
			}
		}

		return $content;
	}

	/**
	 * Set Forum post to spam if spam detected
	 * @param $post_data
	 *
	 * @return mixed
	 */
	public function post_pre_insert( $post_data ) {

		if ( empty( self::$_reason ) ) {
			return $post_data;
		}

		$post_data[ 'post_status' ] = bbp_get_spam_status_id();

		return $post_data;
	}

	/**
	 * Send mail on new Topic
	 *
	 * @param $topic_id
	 */
	public function new_topic( $topic_id ) {

		if ( ! Antispam_Bee::get_option( 'no_notice' ) ) {
			add_post_meta( $topic_id, '_antispam_bee_reason', self::$_reason );
		}

		if ( ! Antispam_Bee::get_option( 'email_notify' ) || bbp_get_topic_status( $topic_id ) !== bbp_get_spam_status_id() ) {
			return;
		}

		$author_mail = bbp_get_topic_author_email( $topic_id );
		$author_name = bbp_get_topic_author_display_name( $topic_id );
		$subject         = sprintf( __( '[%1$s] SPAM topic: "%2$s"', 'antispam-bee' ), html_entity_decode( get_option( 'blogname' ), ENT_QUOTES, get_option( 'blog_charset' ) ), html_entity_decode( bbp_get_topic_title( $topic_id ), ENT_QUOTES, get_option( 'blog_charset' ) ) );
		$notify_message  = sprintf( __( 'New "marked as SPAM" topic "%s"', 'antispam-bee' ), html_entity_decode( bbp_get_topic_title( $topic_id ) ), ENT_QUOTES, get_option( 'blog_charset' ) ) . "\r\n";
		$notify_message .= sprintf( __( 'Author : %1$s (IP: %2$s , %3$s)', 'antispam-bee' ), $author_name, bbp_current_author_ip(), @gethostbyaddr( bbp_current_author_ip() ) ) . "\r\n";
		$notify_message .= sprintf( __( 'E-mail : %s', 'antispam-bee' ), $author_mail ) . "\r\n";
		$notify_message .= sprintf( __( 'URL    : %s', 'antispam-bee' ), bbp_topic_author_url( $topic_id ) ) . "\r\n";
		$notify_message .= sprintf( __( 'Whois  : http://whois.arin.net/rest/ip/%s', 'antispam-bee' ), bbp_current_author_ip() ) . "\r\n";
		$notify_message .= __( 'Topic text: ', 'antispam-bee' ) . "\r\n" . strip_tags( html_entity_decode( bbp_get_topic_content( $topic_id ), ENT_QUOTES, get_option( 'blog_charset' ) ) ) . "\r\n\r\n";
		$notify_message .= sprintf( __( 'Permalink: %s', 'antispam-bee' ), bbp_get_topic_permalink( $topic_id ) ) . "\r\n";

		$wp_email = 'bbPress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER[ 'SERVER_NAME' ] ) );

		if ( '' === $author_name ) {
			$from = "From: \"" . html_entity_decode( get_option( 'blogname' ), ENT_QUOTES, get_option( 'blog_charset' ) ) . "\" <$wp_email>";
			if ( '' != $author_mail ) {
				$reply_to = "Reply-To: $author_mail";
			}
		}
		else {
			$from = "From: \"$author_name\" <$wp_email>";
			if ( '' != $author_mail ) {
				$reply_to = "Reply-To: $author_mail";
			}
		}
		$message_headers = "$from\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";

		if ( isset( $reply_to ) ) {
			$message_headers .= $reply_to . "\n";
		}

		@wp_mail( get_bloginfo( 'admin_email' ), $subject, $notify_message, $message_headers );
	}

	/**
	 * Send mail on new Reply on Topic
	 *
	 * @param $reply_id
	 * @param $topic_id
	 */
	public function new_reply( $reply_id, $topic_id ) {

		if ( ! Antispam_Bee::get_option( 'no_notice' ) ) {
			add_post_meta( $reply_id, '_antispam_bee_reason', self::$_reason );
		}

		if ( ! Antispam_Bee::get_option( 'email_notify' ) || bbp_get_reply_status( $reply_id ) !== bbp_get_spam_status_id() ) {
			return;
		}

		$author_mail = bbp_get_reply_author_email( $reply_id );
		$author_name = bbp_get_reply_author_display_name( $reply_id );
		$subject         = sprintf( __( '[%1$s] SPAM reply to: "%2$s"', 'antispam-bee' ), html_entity_decode( get_option( 'blogname' ), ENT_QUOTES, get_option( 'blog_charset' ) ), html_entity_decode( bbp_get_topic_title( $topic_id ), ENT_QUOTES, get_option( 'blog_charset' ) ) );
		$notify_message  = sprintf( __( 'New "marked as SPAM" reply to "%s"', 'antispam-bee' ), html_entity_decode( bbp_get_topic_title( $topic_id ), ENT_QUOTES, get_option( 'blog_charset' ) ) ) . "\r\n";
		$notify_message .= sprintf( __( 'Author : %1$s (IP: %2$s , %3$s)', 'antispam-bee' ), $author_name, bbp_current_author_ip(), @gethostbyaddr( bbp_current_author_ip() ) ) . "\r\n";
		$notify_message .= sprintf( __( 'E-mail : %s', 'antispam-bee' ), $author_mail ) . "\r\n";
		$notify_message .= sprintf( __( 'URL    : %s', 'antispam-bee' ), bbp_reply_author_url( $reply_id ) ) . "\r\n";
		$notify_message .= sprintf( __( 'Whois  : http://whois.arin.net/rest/ip/%s', 'antispam-bee' ), bbp_current_author_ip() ) . "\r\n";
		$notify_message .= __( 'Reply text: ', 'antispam-bee' ) . "\r\n" . strip_tags( html_entity_decode( bbp_get_reply_content( $reply_id ), ENT_QUOTES, get_option( 'blog_charset' ) ) ) . "\r\n\r\n";
		$notify_message .= sprintf( __( 'Permalink: %s', 'antispam-bee' ), bbp_get_reply_url( $reply_id ) ) . "\r\n";

		$wp_email = 'bbPress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER[ 'SERVER_NAME' ] ) );

		if ( '' == $author_name ) {
			$from = "From: \"" . html_entity_decode( get_option( 'blogname' ), ENT_QUOTES, get_option( 'blog_charset' ) ) . "\" <$wp_email>";
			if ( '' != $author_mail ) {
				$reply_to = "Reply-To: $author_mail";
			}
		}
		else {
			$from = "From: \"$author_name\" <$wp_email>";
			if ( '' != $author_mail ) {
				$reply_to = "Reply-To: $author_mail";
			}
		}

		$message_headers = "$from\n" . "Content-Type: text/plain; charset=\"" . get_option( 'blog_charset' ) . "\"\n";

		if ( isset( $reply_to ) ) {
			$message_headers .= $reply_to . "\n";
		}

		@wp_mail( get_bloginfo( 'admin_email' ), $subject, $notify_message, $message_headers );
	}

	/**
	 * Add Spam Column to form threads and topics
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public static function manage_columns( $columns ) {

		$options = Antispam_Bee::get_options();
		if ( ! empty( $options['no_notice'] ) ) {
			return $columns;
		}

		if ( ! isset( $_REQUEST['post_status'] ) || $_REQUEST['post_status'] !== 'spam' ) {
			return $columns;
		}

		$columns['reason'] = __('Spam Reason', 'antispam-bee');

		return $columns;
	}

	/**
	 * Add reasons column
	 *
	 * @param $column
	 * @param $post_id
	 */
	public static function manage_reason_column( $column, $post_id ) {

		if ( $column === 'reason' ) {
			$reason = get_post_meta( $post_id, '_antispam_bee_reason', true );
			if ( ! empty( $reason ) && isset( Antispam_Bee::$defaults['reasons'][$reason] ) ) {
				_e(Antispam_Bee::$defaults['reasons'][$reason], 'antispam-bee');
			} elseif ( ! empty( $reason ) ) {
				echo $reason;
			}
		}
	}

	/**
	 * Delete old spam
	 *
	 * @since   0.1
	 * @change  2.4
	 */

	public static function delete_old_spam()
	{
		/* Anzahl der Tage */
		$days = (int)self::get_option('cronjob_interval');

		/* Kein Wert? */
		if ( empty($days) ) {
			return false;
		}

		$time = time() - ( $days * 3600 * 24 );

		$posts = get_posts(
			array(
				'post_type' => array( 'topic', 'reply' ),
				'post_status' => 'spam',
				'date_query' => array(
					array(
						'before' => array(
							'year'  => date('Y', $time ),
							'month' => date('m', $time ),
							'day'   => date('d', $time ),
						)
					)
				),
				'numberposts' => -1
			)
		);

		if ( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				wp_delete_post( $post->ID, true );
			}
		}
	}
}

/* Fire bbPress */
add_action(
	'bbp_init',
	array(
		'Antispam_Bee_bbPress',
		'init'
	)
);
