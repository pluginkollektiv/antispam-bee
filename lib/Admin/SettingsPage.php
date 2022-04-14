<?php
/**
 * The settings page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\Inline;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\Components;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Helpers\Settings;

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
	 * Tabs
	 *
	 * @var Tab[]
	 */
	private $tabs = [];

	/**
	 * The slug used for the Settings page
	 *
	 * @var string
	 */
	const SETTINGS_PAGE_SLUG = 'antispam_bee';

	/**
	 * Add Hooks.
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'setup_settings' ] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
			self::SETTINGS_PAGE_SLUG,
			[ $this, 'options_page' ]
		);
	}

	/**
	 * Setup tabs content.
	 */
	public function setup_settings() {
		// Todo: Extract parts of the method in new methods.
		$general_section = new Section(
			'general',
			__( 'General', 'antispam-bee' ),
			__( 'Setup global plugin spam settings.', 'antispam-bee' ) );

		$general_section->add_rows( [
			[
				'label' => __( 'Delete old spam', 'antispam-bee' ),
				'fields' => [
					new Checkbox( 'general', [
						'option_name' => 'ab_cronjob_enable',
					] ),
					new Inline( 'general', [
						'input' => new Text( 'general', [
							'input_type' => 'number',
							'input_size' => 'small',
							'option_name' => 'ab_cronjob_interval',
						] ),
						'option_name' => 'ab_cronjob_enable',
						'label' => esc_html( 'Delete existing spam after %s days', 'antispam-bee' ),
						'description' => esc_html( 'Cleaning up the database from old entries', 'antispam-bee' ),
					] ),
				]
			],
			[
				'label' => __( 'Statistics', 'antispam-bee' ),
				'fields' => [
					new Checkbox( 'general', [
						'option_name' => 'ab_dashboard_chart',
						'label' => esc_html( 'Generate statistics as a dashboard widget', 'antispam-bee' ),
						'description' => esc_html( 'Daily updates of spam detection rate', 'antispam-bee' ),
					] ),
					new Checkbox( 'general', [
						'option_name' => 'ab_dashboard_count',
						'label' => esc_html( 'Spam counter on the dashboard', 'antispam-bee' ),
						'description' => esc_html( 'Amount of identified spam comments', 'antispam-bee' )
					] ),
				]
			],
			[
				'label' => __( 'Pings', 'antispam-bee' ),
				'fields' => [
					new Checkbox( 'general', [
						'option_name' => 'ab_ignore_pings',
						'label' => esc_html( 'Do not check trackbacks / pingbacks', 'antispam-bee' ),
						'description' => esc_html( 'No spam check for link notifications', 'antispam-bee' ),
					] ),
				]
			],
			[
				'label' => __( 'Uninstall', 'antispam-bee' ),
				'fields' => [
					new Checkbox( 'general', [
						'option_name' => 'delete_data_on_uninstall',
						'label' => esc_html( 'Delete Antispam Bee data when uninstalling', 'antispam-bee' ),
						'description' => esc_html( 'If checked, you will delete all data Antispam Bee creates, when uninstalling the plugin.', 'antispam-bee' ),
					] ),
				]
			]
		] );

		// Todo: Add a way to build rows and fields with a fluent interface?
		// Todo: Fix the confusing naming. We have a lot of type e.g.
		$general_tab = new Tab(
			'general',
			__( 'General','antispam-bee' ),
			[
				$general_section
			]
		);

		$rules = Rules::get_controllables();
		$post_processors = PostProcessors::get_controllables();
		$types = [];
		foreach ( $rules as $rule ) {
			$types = array_merge( $types, $rule::get_supported_types() );
		}
		$types = array_unique( $types );

		$tabs = [];
		$tabs['general'] = $general_tab;
		foreach ( $types as $type ) {
			$rules_section = new Section(
				'rules',
				__( 'Rules', 'antispam-bee' ),
				__( 'Setup rules.', 'antispam-bee' ),
				$type
			);
			$rules_section->add_controllables( Components::filter( $rules, [
				'type' => $type,
			] ) );

			$post_processors_section = new Section(
				'post_processors',
				__( 'Post Processors', 'antispam-bee' ),
				__( 'Setup post processors.', 'antispam-bee' ),
				$type
			);

			$post_processors_section->add_controllables( Components::filter( $post_processors, [
				'type' => $type,
			] ) );

			$tabs[$type] = new Tab(
				$type,
				ItemTypeHelper::get_type_name( $type ),
				[
					'rules' => $rules_section,
					'post_processors' => $post_processors_section
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

				add_settings_section( $section->get_name(), $section->get_title(), [ $section, 'get_callback' ], self::SETTINGS_PAGE_SLUG );

				foreach ( $section->get_rows() as $row ) {
					add_settings_field(
						sanitize_title( $row['label'] ),
						$row['label'],
						function() use ( $row ) {
							foreach ( $row['fields'] as $key => $field ) {
								$field->render();
								if ( $key !== count( $row['fields'] ) - 1 ) {
									if ( $field instanceof Checkbox && empty( $field->get_label() ) ) {
										continue;
									}
									echo '<br>';
								}
							}
						},
						self::SETTINGS_PAGE_SLUG,
						$section->get_name()
					);

					register_setting( self::SETTINGS_PAGE_SLUG, Settings::ANTISPAM_BEE_OPTION_NAME, [
						'sanitize_callback' => [ Settings::class, 'sanitize' ],
					] );
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
							<a href="?page=antispam_bee&tab=<?php echo esc_attr( $tab->get_slug() ); ?>" class="nav-tab nav-tab-active"><?php echo esc_html( $tab->get_title() ); ?></a>
						<?php else : ?>
							<a href="?page=antispam_bee&tab=<?php echo esc_attr( $tab->get_slug() ); ?>" class="nav-tab"><?php echo esc_html( $tab->get_title() ); ?></a>
						<?php endif; ?>
				<?php endforeach; ?>
			</h2>

			<form action="<?php echo esc_url( add_query_arg( 'tab', $this->active_tab, admin_url( 'options.php' ) ) ); ?>" method="post">
				<input type="hidden" name="action" value="ab_save_changes"/>

				<?php settings_fields( self::SETTINGS_PAGE_SLUG ); ?>
				<?php do_settings_sections( self::SETTINGS_PAGE_SLUG, $this->active_tab ); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
