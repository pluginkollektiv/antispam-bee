<?php
/**
 * Class registering columns for the "spam comments view".
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Handlers\PluginUpdate;
use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\DashboardHelper;
use AntispamBee\Helpers\Settings;
use AntispamBee\Helpers\SpamReasonTextHelper;
use AntispamBee\PostProcessors\SaveReason;
use WP_Comment_Query;
use wpdb;

/**
 * Class CommentsColumns
 */
class CommentsColumns {

	/**
	 * Registers the module hooks.
	 */
	public static function init(): void {
		if ( ! DashboardHelper::is_edit_spam_comments_page() ) {
			return;
		}

		$supported_types = SaveReason::get_supported_types();
		$show_column     = false;
		foreach ( $supported_types as $type ) {
			$show_column = Settings::get_option( SaveReason::get_option_name( 'active' ), $type );
			if ( $show_column ) {
				break;
			}
		}
		if ( ! $show_column ) {
			return;
		}

		add_filter( 'manage_edit-comments_columns', [ __CLASS__, 'register_plugin_columns' ] );
		add_filter( 'manage_comments_custom_column', [ __CLASS__, 'print_plugin_column' ], 10, 2 );
		add_filter( 'manage_edit-comments_sortable_columns', [ __CLASS__, 'register_sortable_columns' ] );
		add_action( 'pre_get_comments', [ __CLASS__, 'set_orderby_query' ] );
		add_action( 'restrict_manage_comments', [ __CLASS__, 'filter_columns' ] );
		add_action( 'pre_get_comments', [ __CLASS__, 'filter_by_spam_reason' ] );
		add_filter( 'admin_print_styles-edit-comments.php', [ __CLASS__, 'print_column_styles' ] );
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
	public static function register_plugin_columns( array $columns ): array {
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
	public static function print_plugin_column( string $column, int $comment_id ): void {
		if ( 'antispam_bee_reason' !== $column ) {
			return;
		}

		$spam_reason = get_comment_meta( $comment_id, $column, true );

		if ( empty( $spam_reason ) ) {
			echo esc_html_x( 'No data available', 'spam-reason-column-text', 'antispam-bee' );

			return;
		}

		$reasons      = explode( ',', $spam_reason );
		$reason_texts = SpamReasonTextHelper::get_texts_by_slugs( $reasons );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reason texts pre-sanitized.
		echo implode( ',<br>', $reason_texts );
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
	public static function register_sortable_columns( array $columns ): array {
		$columns['antispam_bee_reason'] = 'antispam_bee_reason';

		return $columns;
	}

	/**
	 * Adjust orderby query
	 *
	 * @param WP_Comment_Query $query Current WordPress query.
	 *
	 * @since   2.6.3
	 * @change  2.6.3
	 */
	public static function set_orderby_query( WP_Comment_Query $query ): void {
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
	 * @global wpdb $wpdb
	 */
	public static function filter_columns(): void {
		global $wpdb;
		?>
		<label class="screen-reader-text"
				for="filter-by-comment-spam-reason"><?php esc_html_e( 'Filter by spam reason', 'antispam-bee' ); ?></label>
		<select id="filter-by-comment-spam-reason" name="comment_spam_reason">
			<option value=""><?php esc_html_e( 'All spam reasons', 'antispam-bee' ); ?></option>
			<?php
			$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$tmp     = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}commentmeta WHERE meta_key = 'antispam_bee_reason' GROUP BY meta_value", ARRAY_A );
			$reasons = [];
			foreach ( $tmp as $t ) {
				$reasons = array_merge( $reasons, explode( ',', $t['meta_value'] ) );
			}

			$reasons        = array_unique( $reasons );
			$reason_mapping = PluginUpdate::$spam_reasons_mapping;

			foreach ( $reasons as $reason ) {
				if ( isset( $reason_mapping[ $reason ] ) ) {
					if ( in_array( $reason_mapping[ $reason ], $reasons ) ) {
						continue;
					}

					$reason = $reason_mapping[ $reason ];
				}
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $reason ),
					selected( $spam_reason, $reason, false ),
					esc_html( SpamReasonTextHelper::get_texts_by_slugs( [ $reason ] )[0] )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Filter comments by the spam reason
	 *
	 * @param WP_Comment_Query $query Current WordPress query.
	 */
	public static function filter_by_spam_reason( WP_Comment_Query $query ): void {
		$spam_reason = isset( $_GET['comment_spam_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['comment_spam_reason'] ) ) : '';
		if ( empty( $spam_reason ) ) {
			return;
		}

		$reasons_mapping = PluginUpdate::$spam_reasons_mapping;
		if ( ! in_array( $spam_reason, $reasons_mapping ) && ! isset( $reasons_mapping[ $spam_reason ] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->query_vars['meta_key'] = 'antispam_bee_reason';
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$query->query_vars['meta_value'] = $spam_reason;
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_compare
			$query->query_vars['meta_compare'] = 'LIKE';

			return;
		}

		$query->query_vars['meta_query'] = [
			'relation' => 'OR',
			[
				'key'     => 'antispam_bee_reason',
				'value'   => $spam_reason,
				'compare' => 'LIKE',
			],
			[
				'key'     => 'antispam_bee_reason',
				'value'   => array_flip( $reasons_mapping )[ $spam_reason ],
				'compare' => 'LIKE',
			],
		];
	}

	/**
	 * Print CSS for the plugin column
	 *
	 * @since   2.6.1
	 * @change  2.6.1
	 */
	public static function print_column_styles(): void {
		?>
		<style>
			.column-antispam_bee_reason {
				width: 10%;
			}
		</style>
		<?php
	}
}
