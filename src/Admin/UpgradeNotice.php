<?php
/**
 * Render the upgrade notice on the plugins list page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

/**
 * Class UpgradeNotice
 */
class UpgradeNotice {

	/**
	 * Register the upgrade notice hook.
	 */
	public static function init(): void {
		add_action(
			'in_plugin_update_message-' . plugin_basename( \AntispamBee\MAIN_PLUGIN_FILE ),
			[ __CLASS__, 'render' ],
			10,
			2
		);
	}

	/**
	 * Render the upgrade notice below the plugin row on the plugins list.
	 *
	 * Called via the `in_plugin_update_message-{file}` action when WordPress
	 * detects an available update for the plugin. Displays the `Upgrade Notice`
	 * section from readme.txt inline so editors see breaking-change warnings
	 * without leaving the plugins list.
	 *
	 * @param array     $plugin_data Plugin header data from the local plugin file.
	 * @param \stdClass $response    Update response object from the WordPress.org API.
	 *
	 * @since 3.0.0
	 */
	public static function render( array $plugin_data, \stdClass $response ): void {
		if ( empty( $response->upgrade_notice ) ) {
			return;
		}

		printf(
			'<div class="update-message">%s</div>',
			wp_kses(
				wpautop( $response->upgrade_notice ),
				[
					'p'      => [],
					'br'     => [],
					'a'      => [
						'href'  => [],
						'title' => [],
					],
					'strong' => [],
					'em'     => [],
					'ul'     => [],
					'ol'     => [],
					'li'     => [],
				]
			)
		);
	}
}
