<?php

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;

/**
 * Antispam Bee Settings Page
 */
class SettingsPage {
    /**
     * Tabs
     * 
     * @var Tab[]
     */
    private $tabs = [];

    /**
     * Add Hooks.
     */
    public function init() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'setup_tabs' ] );
    }

    /**
     * Add settings page.
     */
    public function menu() {
        add_options_page(
            __('Antispam Bee', 'antispam-bee' ), 
            __('Antispam Bee', 'antispam-bee' ),
            'manage_options',
            'antispam_bee',
            [ $this, 'settings_page' ]
        );
    }

    /**
     * Settings page content.
     */
    public function settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab']: 'general';
        $settings_name = 'antispam_bee_' . $active_tab;

        ?>
        <div class="wrap" id="ab_main">
			<h2><?php esc_html_e( 'Antispam Bee', 'antispam-bee' ); ?></h2>

            <h2 class="nav-tab-wrapper">
                <?php foreach( $this->tabs as $tab ): ?>
                    <a href="?page=antispam_bee&tab=<?php echo $tab->get_name(); ?>" class="nav-tab"><?php echo $tab->get_title(); ?></a>
                <?php endforeach; ?>
            </h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes" />

				<?php settings_fields( 'antispam_bee' ); ?>
                <?php do_settings_sections( 'antispam_bee' ); ?>  
                <?php submit_button(); ?>
			</form>
		</div>
        <?php
    }

    /**
     * Setup tabs content.
     */
    public function setup_tabs() {
        $this->tabs[] = new Tab(
            'general',  __( 'General', 'antispam-bee' ), 
            [ 
                new Section( 
                    'global_settings', 
                    __('Global Settings', 'antispam-bee' ),
                    __('Setup global plugin spam settings.', 'antispam-bee' ),// @todo Description?
                    [
                        new Checkbox( 'ab_dashboard_chart',   esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ), esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ) ),
                        new Checkbox( 'ab_dashboard_count',   esc_html( 'Spam counter on the dashboard', 'antispam-bee' ), esc_html( 'Amount of identified spam comments', 'antispam-bee' ) ),
                        new Checkbox( 'ab_ignore_pings',      esc_html( 'Do not check trackbacks / pingbacks', 'antispam-bee' ), esc_html( 'No spam check for link notifications', 'antispam-bee' ) ),
                        new Checkbox( 'ab_use_output_buffer', esc_html( 'Check complete site markup for comment forms', 'antispam-bee' ), sprintf( /* translators: s=filter name */  esc_html( 'Uses output buffering instead of the %s filter.', 'antispam-bee' ), '<code>comment_form_field_comment</code>' ) )
                    ]
                )
            ]
        );

        $this->tabs = apply_filters( 'antispam_bee_tabs', $this->tabs );

        // Register option setting
        foreach( $this->tabs as $tab ) {
            foreach ( $tab->get_sections() as $section ) {
                add_settings_section( $section->get_name(), $section->get_title(), [ $section, 'get_callback' ], 'antispam_bee' );

                foreach( $section->get_fields() as $field ) {
                    add_settings_field(
                        $field->get_name(),
                        $field->get_label(),
                        [ $field, 'render' ],
                        'antispam_bee',
                        $section->get_name()
                    );

                    register_setting( 'antispam_bee', $field->get_name() );
                }
            }
        }
    }
}