<?php
/**
 * Class registering columns for the "spam comments view".
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Class CommentsColumns
 */
class CommentsColumns {

	/**
	 * The known spam reasons.
	 *
	 * @var array
	 */
	public static $reasons = [];

	/**
	 * Registers the module hooks.
	 */
	public function init() {
		add_filter( 'manage_edit-comments_columns', [ $this, 'register_plugin_columns' ] );
		add_filter( 'manage_comments_custom_column', [ $this, 'print_plugin_column' ], 10, 2 );
		add_filter( 'manage_edit-comments_sortable_columns', [ $this, 'register_sortable_columns' ] );
		add_action( 'pre_get_comments', [ $this, 'set_orderby_query' ] );
		add_action( 'restrict_manage_comments', [ $this, 'filter_columns' ] );
		add_action( 'pre_get_comments', [ $this, 'filter_by_spam_reason' ] );
		add_filter( 'admin_print_styles-edit-comments.php', [ $this, 'print_column_styles' ] );

		// @todo: replace this with the dynamic list of spam reasons from the rules list.
		$default_reasons = [
			'css'           => esc_attr__( 'Honeypot', 'antispam-bee' ),
			'time'          => esc_attr__( 'Comment time', 'antispam-bee' ),
			'empty'         => esc_attr__( 'Empty Data', 'antispam-bee' ),
			'localdb'       => esc_attr__( 'Local DB Spam', 'antispam-bee' ),
			'server'        => esc_attr__( 'Fake IP', 'antispam-bee' ),
			'country'       => esc_attr__( 'Country Check', 'antispam-bee' ),
			'bbcode'        => esc_attr__( 'BBCode', 'antispam-bee' ),
			'lang'          => esc_attr__( 'Comment Language', 'antispam-bee' ),
			'regexp'        => esc_attr__( 'Regular Expression', 'antispam-bee' ),
			'title_is_name' => esc_attr__( 'Identical Post title and blog title', 'antispam-bee' ),
			'manually'      => esc_attr__( 'Manually', 'antispam-bee' ),
		];

		self::$reasons = apply_filters( 'asb_spam_reasons_list', $default_reasons );
	}

	/**
	 * Register plugin columns on comments screen.
	 *
	 * @param array $columns Array with existing columns.
	 *
	 * @return  array          Array with extended columns.
	 * @since   2.6.0
	 * @change  2.6.0
	 */
	public static function register_plugin_columns( $columns ) {
		return array_merge(
			$columns,
			[
				'antispam_bee_reason' => esc_html__( 'Spam Reason', 'antispam-bee' ),
			]
		);
	}

	/**
	 * Display plugin column values on comments screen
	 *
	 * @param string  $column Currently selected column.
	 * @param integer $comment_id Comment ID.
	 *
	 * @since   2.6.0
	 * @change  2.6.0
	 */
	public static function print_plugin_column( $column, $comment_id ) {
		if ( 'antispam_bee_reason' !== $column ) {
			return;
		}

		$spam_reason  = get_comment_meta( $comment_id, $column, true );
		var_dump($spam_reason);
		$spam_reasons = self::$reasons;

		if ( empty( $spam_reason ) || empty( $spam_reasons[ $spam_reason ] ) ) {
			return;
		}

		echo esc_html( $spam_reasons[ $spam_reason ] );
	}

	/**
	 * Register plugin sortable columns on comments screen
	 *
	 * @param array $columns Registered columns.
	 *
	 * @return  array  $columns Columns with AB field.
	 * @since   2.6.3
	 * @change  2.6.3
	 */
	public static function register_sortable_columns( $columns ) {
		$columns['antispam_bee_reason'] = 'antispam_bee_reason';

		return $columns;
	}

	/**
	 * Adjust orderby query
	 *
	 * @param \WP_Comment_Query $query Current WordPress query.
	 *
	 * @since   2.6.3
	 * @change  2.6.3
	 */
	public static function set_orderby_query( $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';

		if ( empty( $orderby ) || 'antispam_bee_reason' !== $orderby ) {
			return;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->query_vars['meta_key'] = 'antispam_bee_reason';
		$query->query_vars['orderby']  = 'meta_value';
	}

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
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$reasons = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}commentmeta WHERE meta_key = 'antispam_bee_reason' GROUP BY meta_value", ARRAY_A );

			foreach ( $reasons as $reason ) {
				if ( ! isset( self::$reasons[ $reason['meta_value'] ] ) ) {
					continue;
				}
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $reason['meta_value'] ),
					selected( $spam_reason, $reason['meta_value'], false ),
					esc_html( self::$reasons[ $reason['meta_value'] ] )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Filter comments by the spam reason
	 *
	 * @param \WP_Comment_Query $query Current WordPress query.
	 */
	public static function filter_by_spam_reason( $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
		if ( empty( $spam_reason ) || ! in_array( $spam_reason, array_keys( self::$reasons ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->query_vars['meta_key'] = 'antispam_bee_reason';
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$query->query_vars['meta_value'] = $spam_reason;
	}

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
}
