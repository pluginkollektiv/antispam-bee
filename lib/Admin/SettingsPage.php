<?php
/**
 * The settings page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Admin\Fields\Checkbox;
use AntispamBee\Admin\Fields\Text;
use AntispamBee\Admin\Fields\Select;
use AntispamBee\Admin\Fields\Textarea;
use AntispamBee\Handlers\Rules;
use AntispamBee\Handlers\Trackback;
use AntispamBee\Helpers\InterfaceHelper;
use AntispamBee\Helpers\ItemTypeHelper;
use AntispamBee\Helpers\Settings;
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
			]
		] );

		// Todo: Add a way to build rows and fields with a fluent interface?
		// Todo: Fix the confusing naming. We have a lot of type e.g.

		// Todo: Discuss if we want to remove that setting in V3 and maybe have a filter for that.
		// If we keep it, we have to add it to the comments tab.
		// new Checkbox( 'ab_use_output_buffer', esc_html( 'Check complete site markup for comment forms', 'antispam-bee' ), sprintf( /* translators: s=filter name */ esc_html( 'Uses output buffering instead of the %s filter.', 'antispam-bee' ), '<code>comment_form_field_comment</code>' ) ),
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
				$type,
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

				add_settings_section( $section->get_name(), $section->get_title(), [ $section, 'get_callback' ], self::SETTINGS_PAGE_SLUG );

				foreach ( $section->get_rows() as $row ) {
					add_settings_field(
						sanitize_title( $row['label'] ),
						$row['label'],
						function() use ( $row ) {
							$last_field = false;
							foreach ( $row['fields'] as $key => $field ) {
								$field->render( $last_field );
								if ( $key !== count( $row['fields'] ) - 1 ) {
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
