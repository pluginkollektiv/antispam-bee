<?php

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;

/**
 * Antispam Bee Settings Page
 */
class SettingsPage {
    /**
     * Active section
     * 
     * @var string
     */
    private $active_section = [];

    /**
     * Tabs
     * 
     * @var Section[]
     */
    private $sections = [];

    /**
     * Add Hooks.
     */
    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'setup_settings' ] );

        $this->active_section = isset( $_GET['section'] ) ? $_GET['section']: 'general';
    }

    /**
     * Add settings page.
     */
    public function add_menu() {
        add_options_page(
            __('Antispam Bee', 'antispam-bee' ), 
            __('Antispam Bee', 'antispam-bee' ),
            'manage_options',
            'antispam_bee',
            [ $this, 'options_page' ]
        );
    }

    /**
     * Setup tabs content.
     */
    public function setup_settings() {
        // General
        $sections[] = new Section( 
            'general', 
            __('General', 'antispam-bee' ),
            __('Setup global plugin spam settings.', 'antispam-bee' ),
            [
                new Checkbox( 'ab_dashboard_chart',   esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ), esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ) ),
                new Checkbox( 'ab_dashboard_count',   esc_html( 'Spam counter on the dashboard', 'antispam-bee' ), esc_html( 'Amount of identified spam comments', 'antispam-bee' ) ),
                new Checkbox( 'ab_ignore_pings',      esc_html( 'Do not check trackbacks / pingbacks', 'antispam-bee' ), esc_html( 'No spam check for link notifications', 'antispam-bee' ) ),
                new Checkbox( 'ab_use_output_buffer', esc_html( 'Check complete site markup for comment forms', 'antispam-bee' ), sprintf( /* translators: s=filter name */  esc_html( 'Uses output buffering instead of the %s filter.', 'antispam-bee' ), '<code>comment_form_field_comment</code>' ) )
            ]
        );

       $this->sections = apply_filters( 'antispam_bee_sections', $sections );

       // Register option setting
       foreach ( $this->sections as $section ) {
           if ( $section->get_name() !== $this->active_section ) {
               continue;
           }

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

    /**
     * Settings page content.
     */
    public function options_page() {
        ?>
        <div class="wrap" id="ab_main">
			<h2><?php esc_html_e( 'Antispam Bee', 'antispam-bee' ); ?></h2>

            <h2 class="nav-tab-wrapper">
                <?php foreach( $this->sections as $section ): ?>
                    <?php if( $section->get_name() === $this->active_section ): ?>
                        <a href="?page=antispam_bee&section=<?php echo $section->get_name(); ?>" class="nav-tab nav-tab-active"><?php echo $section->get_title(); ?></a>
                    <?php else: ?>
                        <a href="?page=antispam_bee&section=<?php echo $section->get_name(); ?>" class="nav-tab"><?php echo $section->get_title(); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </h2>

			<form action="<?php echo esc_url( add_query_arg( 'section', $this->active_section, admin_url( 'options.php' ) ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes" />

				<?php settings_fields( 'antispam_bee' ); ?>
                <?php do_settings_sections( 'antispam_bee', $this->active_section ); ?>  
                <?php submit_button(); ?>
			</form>
		</div>
        <?php
    }
}