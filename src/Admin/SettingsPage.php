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

// @todo: add `ids` to the `h2` section headlines. After first analyzation, that seems only to be possible via JS
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
	 * @var Rules[]
	 */
	private $rules = [];

	/**
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
		// Todo: Add a way to build rows and fields with a fluent interface? (Nice-to-have)
		/*
		 * Todo: Instead of using an array to pass options to a function, one could introduce a class that contains
		 *   these options as class attributes. You instantiate an object of this class and pass it to the
		 *   Components::filter() method. For frequently used options, one could also use blueprints for options.
		 *   This would make refactoring easier, but would slightly increase the complexity. (nice-to-have).
		 */
		// Todo: Fix the confusing naming. We have a lot of type e.g. (Nice-to-have)

		$tabs['general'] = new Tab(
			'general',
			__( 'General', 'antispam-bee' )
		);

		$this->rules           = Rules::get_controllables();
		$this->post_processors = PostProcessors::get_controllables();
		$types                 = [];
		foreach ( $this->rules as $rule ) {
			$types = array_merge( $types, $rule::get_supported_types() );
		}
		foreach ( $this->post_processors as $post_processor ) {
			$types = array_merge( $types, $post_processor::get_supported_types() );
		}
		$types = array_unique( $types );

		foreach ( $types as $type ) {
			$tabs[ $type ] = new Tab(
				$type,
				ContentTypeHelper::get_type_name( $type )
			);
		}
		$this->tabs = $tabs;

		$this->populate_tabs();

		// Register option setting
		foreach ( $this->tabs as $tab ) {
			foreach ( $tab->get_sections() as $section ) {
				if ( $tab->get_slug() !== $this->active_tab ) {
					continue;
				}

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

	protected function populate_tabs() {
		$type = $this->active_tab;

		$data = [];

		if ( 'general' === $type ) {
			$data['general'] = [
				'title'         => ContentTypeHelper::get_type_name( 'general' ),
				'description'   => __( 'Setup global plugin spam settings.', 'antispam-bee' ),
				'controllables' => ComponentsHelper::filter( GeneralOptions::get_controllables(), [ 'content_type' => $type ] ),
			];
		}

		$data = array_merge(
			$data,
			[
				'rules'           => [
					'title'         => __( 'Rules', 'antispam-bee' ),
					'description'   => __( 'Setup rules.', 'antispam-bee' ),
					'controllables' => ComponentsHelper::filter( $this->rules, [ 'content_type' => $type ] ),
				],
				'post_processors' => [
					'title'         => __( 'Post Processors', 'antispam-bee' ),
					'description'   => __( 'Setup post processors.', 'antispam-bee' ),
					'controllables' => ComponentsHelper::filter( $this->post_processors, [ 'content_type' => $type ] ),
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
				$type
			);
			$section->add_controllables( $value['controllables'] );
			$this->tabs[ $type ]->add_section( $section );
		}
	}

	/**
	 * Settings page content.
	 */
	public function options_page() {
		?>
		<div class="wrap" id="ab_main">
			<h1><?php esc_html_e( 'Antispam Bee', 'antispam-bee' ); ?></h1>

			<ul class="nav-tab-wrapper">
				<style>.nav-tab-wrapper li { margin-bottom: 0; }</style>
				<?php foreach ( $this->tabs as $tab ) : ?>
				<li>
					<?php if ( $tab->get_slug() === $this->active_tab ) : ?>
						<a href="?page=antispam_bee&tab=<?php echo esc_attr( $tab->get_slug() ); ?>"
						   class="nav-tab nav-tab-active"><?php echo esc_html( $tab->get_title() ); ?></a>
					<?php else : ?>
						<a href="?page=antispam_bee&tab=<?php echo esc_attr( $tab->get_slug() ); ?>"
						   class="nav-tab"><?php echo esc_html( $tab->get_title() ); ?></a>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>

			<form
				action="<?php echo esc_url( add_query_arg( 'tab', $this->active_tab, admin_url( 'options.php' ) ) ); ?>"
				method="post">
				<input type="hidden" name="action" value="ab_save_changes"/>

				<?php settings_fields( self::SETTINGS_PAGE_SLUG ); ?>
				<?php do_settings_sections( self::SETTINGS_PAGE_SLUG ); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
