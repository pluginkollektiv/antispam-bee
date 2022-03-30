<?php

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Handlers\Rules;
use AntispamBee\Handlers\Trackback;
use AntispamBee\Helpers\InterfaceHelper;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Interfaces\Controllable;
use AntispamBee\Interfaces\Verifiable;

/**
 * Antispam Bee Settings Page
 */
class SettingsPage {
	/**
	 * Active tab
	 *
	 * @var string
	 */
	private $active_tab = [];

	/**
	 * Active section
	 *
	 * @var string
	 */
	private $active_section = [];

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
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'setup_settings' ] );

		$this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
	}

	/**
	 * Add settings page.
	 */
	public function add_menu() {
		add_options_page(
			__( 'Antispam Bee', 'antispam-bee' ),
			__( 'Antispam Bee', 'antispam-bee' ),
			'manage_options',
			'antispam_bee',
			[ $this, 'options_page' ]
		);
	}

	/**
	 * Setup tabs content.
	 */
	public function setup_settings() {
		$general_section = new Section(
			'general',
			__( 'General', 'antispam-bee' ),
			__( 'Setup global plugin spam settings.', 'antispam-bee' ) );
		$general_section->add_fields( [
			new Checkbox( 'ab_dashboard_chart', esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ), esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ) ),
			new Checkbox( 'ab_dashboard_count', esc_html( 'Spam counter on the dashboard', 'antispam-bee' ), esc_html( 'Amount of identified spam comments', 'antispam-bee' ) ),
			new Checkbox( 'ab_ignore_pings', esc_html( 'Do not check trackbacks / pingbacks', 'antispam-bee' ), esc_html( 'No spam check for link notifications', 'antispam-bee' ) ),
			new Checkbox( 'ab_use_output_buffer', esc_html( 'Check complete site markup for comment forms', 'antispam-bee' ), sprintf( /* translators: s=filter name */ esc_html( 'Uses output buffering instead of the %s filter.', 'antispam-bee' ), '<code>comment_form_field_comment</code>' ) ),
		] );
		$general_tab = new Tab(
			'general',
			__( 'General','antispam-bee' ),
			[
				$general_section
			]
		);

		$rules = Rules::filter( Rules::get(), [
			'is_controllable' => true,
			'only_active' => true,
		] );
		$types = [];
		foreach ( $rules as $rule ) {
			$types = array_merge( $types, InterfaceHelper::call( $rule, 'controllable', 'get_supported_types' ) );
		}
		$types = array_unique( $types );

		$tabs = [];
		$tabs['general'] = $general_tab;
		foreach ( $types as $type ) {
			$section = new Section(
				'rules',
				__( 'Rules', 'antispam-bee' ),
				__( 'Setup rules.', 'antispam-bee' ),
				$type
			);
			$section->add_controllables( Rules::filter( $rules, [
				'type' => $type,
			] ) );
			$tabs[$type] = new Tab(
				$type . '_tab',
				ItemTypeHelper::get_type_name( $type ),
				[
					'rules' => $section
				]
			);
		}
		$this->tabs = $tabs;

		// Register option setting
		foreach ( $this->tabs as $tab ) {
			foreach ( $tab->get_sections() as $section ) {
				if ( $tab->get_slug() !== $this->active_tab ) {
					continue;
				}

				add_settings_section( $section->get_name(), '<span style="color: #f00">' . $section->get_title() . '</span>', [ $section, 'get_callback' ], 'antispam_bee' );

				foreach ( $section->get_fields() as $field ) {
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

	/**
	 * Settings page content.
	 */
	public function options_page() {
		?>
		<div class="wrap" id="ab_main">
			<h2><?php esc_html_e( 'Antispam Bee', 'antispam-bee' ); ?></h2>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $tab ) : ?>
						<?php if ( $tab->get_slug() === $this->active_tab ) : ?>
							<a href="?page=antispam_bee&tab=<?php echo $tab->get_slug(); ?>" class="nav-tab nav-tab-active"><?php echo $tab->get_title(); ?></a>
						<?php else : ?>
							<a href="?page=antispam_bee&tab=<?php echo $tab->get_slug(); ?>" class="nav-tab"><?php echo $tab->get_title(); ?></a>
						<?php endif; ?>
				<?php endforeach; ?>
			</h2>

			<form action="<?php echo esc_url( add_query_arg( 'tab', $this->active_tab, admin_url( 'options.php' ) ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes"/>

				<?php settings_fields( 'antispam_bee' ); ?>
				<?php do_settings_sections( 'antispam_bee', $this->active_tab ); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
