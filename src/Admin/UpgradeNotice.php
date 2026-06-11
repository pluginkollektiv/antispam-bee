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
	 * Render the upgrade notice below the plugin row on the plugins list.
	 *
	 * Called via the `in_plugin_update_message-{file}` action when WordPress
	 * detects an available update for the plugin. Displays the `Upgrade Notice`
	 * section from readme.txt inline so editors see breaking-change warnings
	 * without leaving the plugins list.
	 *
	 * @since 3.0.0
	 *
	 * @param array $plugin_data Plugin header data returned by the WordPress update API.
	 */
	public static function render( array $plugin_data ): void {
		if ( empty( $plugin_data['upgrade_notice'] ) ) {
			return;
		}

		printf(
			'<div class="update-message">%s</div>',
			wp_kses(
				wpautop( $plugin_data['upgrade_notice'] ),
				[
					'p'      => [],
					'a'      => [
						'href'  => [],
						'title' => [],
					],
					'strong' => [],
					'em'     => [],
				]
			)
		);
	}
}
