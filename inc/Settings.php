<?php

namespace AntispamBee;

use AntispamBee\Rules\Controllable;
use AntispamBee\Rules\Verifiable;

class Settings {
	public static function init() {
		add_action(
			'admin_menu',
			array(
				__CLASS__,
				'add_sidebar_menu',
			)
		);
	}

	public static function add_sidebar_menu() {
		$page = add_options_page(
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam_bee',
			array(
				__CLASS__,
				'options_page',
			)
		);
	}

	public static function options_page() {
		?>
		<div class="wrap" id="ab_main">
			<h2>
				Antispam Bee
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes" />

				<?php
				wp_nonce_field( '_antispam_bee__settings_nonce' );
				$rules = apply_filters( 'asb_rules', [] );
				foreach ( $rules as $rule ) {
					if ( ! isset( $rule['verifiable'] ) ) {
						continue;
					}

					$interfaces = class_implements( $rule['verifiable'] );
					if ( false === $interfaces || empty( $interfaces ) ) {
						continue;
					}

					if ( ! in_array( Controllable::class, $interfaces, true ) ) {
						continue;
					}

					render_option( $rule['verifiable'] );
				}
				?>
			</form>
		</div>
		<?php
	}

	private static function render_option( $verifiable ) {

	}
}
