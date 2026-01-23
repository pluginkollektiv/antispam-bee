<?php
/**
 * The Columns Class.
 *
 * @package Antispam Bee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Antispam_Bee_Columns
 */
final class Antispam_Bee_Columns {

	/**
	 * Register plugin columns on comments screen.
	 *
	 * @since   2.6.0
	 * @change  2.6.0
	 *
	 * @param   array $columns Array with existing columns.
	 * @return  array          Array with extended columns.
	 */
	public static function register_plugin_columns( $columns ) {
		return array_merge(
			$columns,
			array(
				'antispam_bee_reason' => esc_html__( 'Spam Reason', 'antispam-bee' ),
			)
		);
	}

	/**
	 * Display plugin column values on comments screen
	 *
	 * @since   2.6.0
	 * @change  2.6.0
	 *
	 * @param   string  $column      Currently selected column.
	 * @param   integer $comment_id  Comment ID.
	 */
	public static function print_plugin_column( $column, $comment_id ) {
		if ( 'antispam_bee_reason' !== $column ) {
			return;
		}

		$spam_reason  = get_comment_meta( $comment_id, $column, true );
		$spam_reasons = Antispam_Bee::$defaults['reasons'];

		if ( empty( $spam_reason ) || empty( $spam_reasons[ $spam_reason ] ) ) {
			return;
		}

		echo esc_html( $spam_reasons[ $spam_reason ] );
	}

	/**
	 * Register plugin sortable columns on comments screen
	 *
	 * @since   2.6.3
	 * @change  2.6.3
	 *
	 * @param   array $columns  Registered columns.
	 * @return  array  $columns Columns with AB field.
	 */
	public static function register_sortable_columns( $columns ) {
		$columns['antispam_bee_reason'] = 'antispam_bee_reason';

		return $columns;
	}

    // phpcs:disable WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
    // phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
	/**
	 * Adjust orderby query
	 *
	 * @since   2.6.3
	 * @change  2.6.3
	 *
	 * @param   \WP_Comment_Query $query  Current WordPress query.
	 */
	public static function set_orderby_query( $query ) {
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';

		if ( empty( $orderby ) || 'antispam_bee_reason' !== $orderby ) {
			return;
		}

		$query->query_vars['meta_key'] = 'antispam_bee_reason';
		$query->query_vars['orderby']  = 'meta_value';
	}
    // phpcs:enable WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
    // phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

	//phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
	/**
	 * Filter comments by the spam reason
	 *
	 * @global \wpdb $wpdb
	 */
	public static function filter_columns() {
		global $wpdb;
		?>
		<label class="screen-reader-text" for="filter-by-comment-spam-reason"><?php esc_html_e( 'Filter by spam reason', 'antispam-bee' ); ?></label>
		<select id="filter-by-comment-spam-reason" name="comment_spam_reason">
			<option value=""><?php esc_html_e( 'All spam reasons', 'antispam-bee' ); ?></option>
			<?php
			$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
			$reasons     = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}commentmeta WHERE meta_key = 'antispam_bee_reason' group by meta_value", ARRAY_A );

			foreach ( $reasons as $reason ) {
				if ( ! isset( Antispam_Bee::$defaults['reasons'][ $reason['meta_value'] ] ) ) {
					continue;
				}
				$label = Antispam_Bee::$defaults['reasons'][ $reason['meta_value'] ];
				echo "\t" . '<option value="' . esc_attr( $reason['meta_value'] ) . '"' . selected( $spam_reason, $reason['meta_value'], false ) . '>' . esc_html( $label ) . "</option>\n";
			}
			?>
		</select>
		<?php
	}
    //phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

    //phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
    //phpcs:disable WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
    //phpcs:disable WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
	/**
	 * Filter comments by the spam reason
	 *
	 * @param \WP_Comment_Query $query  Current WordPress query.
	 */
	public static function filter_by_spam_reason( $query ) {
		$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
		if ( empty( $spam_reason ) || ! in_array( $spam_reason, array_keys( Antispam_Bee::$defaults['reasons'] ), true ) ) {
			return;
		}

		$query->query_vars['meta_key']   = 'antispam_bee_reason';
		$query->query_vars['meta_value'] = $spam_reason;
	}
    //phpcs:enable WordPress.VIP.SlowDBQuery.slow_db_query_meta_key
    //phpcs:enable WordPress.VIP.SlowDBQuery.slow_db_query_meta_value
    //phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

	/**
	 * Print CSS for the plugin column
	 *
	 * @since   2.6.1
	 * @change  2.6.1
	 */
	public static function print_column_styles() {
		?>
		<style>
			.column-antispam_bee_reason {
				width: 10%;
			}
		</style>
		<?php
	}

	/**
	 * Add comment action link to report spam to ASB.
	 *
	 * @since 2.9.3
	 *
	 * @param string[]   $actions Array of actions.
	 * @param WP_Comment $comment Comment object.
	 */
	public static function add_report_comment_action_link( $actions, $comment ) {

		// URLencode comment data.
		$name    = rawurlencode( $comment->comment_author );
		$email   = rawurlencode( $comment->comment_author_email );
		$ip      = rawurlencode( $comment->comment_author_IP );
		$host    = rawurlencode( gethostbyaddr( $comment->comment_author_IP ) );
		$url     = rawurlencode( $comment->comment_author_url );
		$content = rawurlencode( $comment->comment_content );
		$agent   = rawurlencode( $comment->comment_agent );

		// Build action link.
		$target = ' target="_blank" ';
		$rel    = ' rel="noopener noreferrer" ';
		$href   = 'href="https://docs.google.com/forms/d/e/1FAIpQLSeQlKVZZYsF1qkKz7U78B2wy_6s6I7aNSdQc-DGpjeqWx70-A/viewform?c=0&w=1&entry.437446945=' . $name . '&entry.462884433=' . $ip . '&entry.1346967038=' . $host . '&entry.121560485=' . $email . '&entry.1210529682=' . $url . '&entry.1837399577=' . $content . '&entry.372858475=' . $agent . '" ';

		$action  = '';
		$action .= "<a $target $href $rel>";
		$action .= __( 'Report to Antispam Bee', 'antispam-bee' );
		$action .= '</a>';

		$actions['report_spam trash'] = $action;

		return $actions;
	}
}
