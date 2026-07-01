<?php
/**
 * The settings page.
 *
 * @package AntispamBee\Admin
 */

namespace AntispamBee\Admin;

use AntispamBee\Handlers\GeneralOptions;
use AntispamBee\Handlers\PostProcessors;
use AntispamBee\Handlers\Rules;
use AntispamBee\Helpers\ComponentsHelper;
use AntispamBee\Helpers\ContentTypeHelper;
use AntispamBee\Helpers\Sanitize;
use AntispamBee\Helpers\Settings;
use const AntispamBee\MAIN_PLUGIN_FILE;
use const AntispamBee\PLUGIN_VERSION;

/**
 * Antispam Bee Settings Page
 */
class SettingsPage {
	/**
	 * Active tab
	 *
	 * @var string
	 */
	private $active_tab = '';

	/**
	 * Tabs
	 *
	 * @var Tab[]
	 */
	private $tabs = [];

	/**
	 * List of controllable rules.
	 *
	 * @var Rules[]
	 */
	private $rules = [];

	/**
	 * List of controllable post processors
	 *
	 * @var PostProcessors[]
	 */
	private $post_processors = [];

	/**
	 * The slug used for the Settings page
	 *
	 * @var string
	 */
	const SETTINGS_PAGE_SLUG = 'antispam_bee';

	/**
	 * Add Hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'setup_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( MAIN_PLUGIN_FILE ), [ $this, 'add_action_links' ] );
		add_filter( 'plugin_row_meta', [ $this, 'add_row_meta' ], 10, 2 );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	}

	/**
	 * Enqueue admin stylesheet on the settings page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( 'settings_page_' . self::SETTINGS_PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'antispam-bee-admin',
			plugin_dir_url( MAIN_PLUGIN_FILE ) . 'assets/css/admin.css',
			[],
			PLUGIN_VERSION
		);

		wp_enqueue_script(
			'antispam-bee-admin-tabs',
			plugin_dir_url( MAIN_PLUGIN_FILE ) . 'assets/js/admin-tabs.js',
			[],
			PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Add settings page.
	 */
	public function add_menu(): void {
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
	public function setup_settings(): void {
		$tabs['general'] = new Tab(
			'general',
			__( 'General', 'antispam-bee' )
		);

		$this->rules           = Rules::get_controllables();
		$this->post_processors = PostProcessors::get_controllables();
		$reaction_types        = [];
		foreach ( $this->rules as $rule ) {
			$reaction_types = array_merge( $reaction_types, $rule::get_supported_types() );
		}
		foreach ( $this->post_processors as $post_processor ) {
			$reaction_types = array_merge( $reaction_types, $post_processor::get_supported_types() );
		}
		$reaction_types = array_unique( $reaction_types );

		foreach ( $reaction_types as $reaction_type ) {
			$tabs[ $reaction_type ] = new Tab(
				$reaction_type,
				ContentTypeHelper::get_reaction_type_name( $reaction_type )
			);
		}
		$this->tabs = $tabs;

		$this->populate_tabs();

		// Register sections for all tabs.
		foreach ( $this->tabs as $tab ) {
			foreach ( $tab->get_sections() as $section ) {
				$section->render();
			}
		}

		register_setting(
			self::SETTINGS_PAGE_SLUG,
			Settings::OPTION_NAME,
			[
				'sanitize_callback' => [ Sanitize::class, 'sanitize_options' ],
			]
		);
	}

	/**
	 * Populate settings tabs.
	 *
	 * @return void
	 */
	protected function populate_tabs(): void {
		foreach ( $this->tabs as $tab ) {
			$reaction_type = $tab->get_slug();
			$data          = [];

			if ( 'general' === $reaction_type ) {
				$data['general'] = [
					'title'         => ContentTypeHelper::get_reaction_type_name( 'general' ),
					'description'   => __( 'Setup global plugin spam settings.', 'antispam-bee' ),
					'controllables' => ComponentsHelper::filter( GeneralOptions::get_controllables(), [ 'reaction_type' => $reaction_type ] ),
				];
			}

			$data = array_merge(
				$data,
				[
					'rules'           => [
						'title'         => __( 'Rules', 'antispam-bee' ),
						'description'   => __( 'Setup rules.', 'antispam-bee' ),
						'controllables' => ComponentsHelper::filter( $this->rules, [ 'reaction_type' => $reaction_type ] ),
					],
					'post_processors' => [
						'title'         => __( 'Post Processors', 'antispam-bee' ),
						'description'   => __( 'Setup post processors.', 'antispam-bee' ),
						'controllables' => ComponentsHelper::filter( $this->post_processors, [ 'reaction_type' => $reaction_type ] ),
					],
				]
			);

			foreach ( $data as $key => $value ) {
				if ( empty( $value['controllables'] ) ) {
					continue;
				}

				$section = new Section(
					$key,
					$value['title'],
					$value['description'],
					$reaction_type
				);
				$section->add_controllables( $value['controllables'] );
				$this->tabs[ $reaction_type ]->add_section( $section );
			}
		}
	}

	/**
	 * Settings page content.
	 */
	public function options_page(): void {
		?>
		<div class="wrap" id="ab_main">
			<h1><?php esc_html_e( 'Antispam Bee', 'antispam-bee' ); ?></h1>

			<div class="nav-tab-wrapper" role="tablist">
				<?php
				foreach ( $this->tabs as $tab ) :
					$is_active = $tab->get_slug() === $this->active_tab;
					?>
					<button
							type="button"
							id="tab-<?php echo esc_attr( $tab->get_slug() ); ?>"
							class="nav-tab<?php echo $is_active ? ' nav-tab-active' : ''; ?>"
							data-tab="<?php echo esc_attr( $tab->get_slug() ); ?>"
							role="tab"
							aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
							tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
					><?php echo esc_html( $tab->get_title() ); ?></button>
				<?php endforeach; ?>
			</div>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php settings_fields( self::SETTINGS_PAGE_SLUG ); ?>

				<?php
				foreach ( $this->tabs as $tab ) :
					$is_active = $tab->get_slug() === $this->active_tab;
					?>
				<div
					id="nav-tab__content--<?php echo esc_attr( $tab->get_slug() ); ?>"
					class="nav-tab__content"
					role="tabpanel"
					aria-labelledby="tab-<?php echo esc_attr( $tab->get_slug() ); ?>"
					<?php echo $is_active ? '' : 'hidden'; ?>
				>
					<?php do_settings_sections( self::SETTINGS_PAGE_SLUG . '_' . $tab->get_slug() ); ?>

					<div class="ab-action-row">
						<?php submit_button(); ?>

						<?php if ( 'general' === $this->active_tab ) : ?>
							<nav class="ab-help-links" aria-label="<?php echo esc_attr__( 'Plugin resources', 'antispam-bee' ); ?>">
								<ul>
									<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Donate', 'antispam-bee' ); ?></a></li>
									<li><a href="<?php echo esc_url( __( 'https://wordpress.org/plugins/antispam-bee/#faq', 'antispam-bee' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'FAQ', 'antispam-bee' ); ?></a></li>
									<li><a href="<?php echo esc_url( __( 'https://antispambee.pluginkollektiv.org/documentation', 'antispam-bee' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manual', 'antispam-bee' ); ?></a></li>
									<li><a href="https://wordpress.org/support/plugin/antispam-bee/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'antispam-bee' ); ?></a></li>
								</ul>
							</nav>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add a Settings link to the plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( array $links ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		return array_merge(
			[
				'<a href="' . esc_url( add_query_arg( 'page', self::SETTINGS_PAGE_SLUG, admin_url( 'options-general.php' ) ) ) . '">' . esc_html__( 'Settings', 'antispam-bee' ) . '</a>',
			],
			$links
		);
	}

	/**
	 * Add Donate and Support links to the plugin row meta.
	 *
	 * @param array  $links Existing row meta links.
	 * @param string $file  Plugin basename of the current row.
	 * @return array Modified row meta links.
	 */
	public function add_row_meta( array $links, string $file ): array {
		if ( plugin_basename( MAIN_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		return array_merge(
			$links,
			[
				'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'antispam-bee' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/antispam-bee" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'antispam-bee' ) . '</a>',
			]
		);
	}
}
