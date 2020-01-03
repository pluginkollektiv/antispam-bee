<?php
/**
 * Renders the settings and saves the data.
 *
 * @package Antispam Bee Settings
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Settings;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;
use Pluginkollektiv\AntispamBee\Repository\FilterRepository;
use Pluginkollektiv\AntispamBee\Repository\PostProcessorRepository;

/**
 * Class Controller
 *
 * @package Pluginkollektiv\AntispamBee\Settings
 */
class Controller {


	/**
	 * The hook of the settings page.
	 *
	 * @var string $hook
	 */
	private $hook;

	/**
	 * The directory, where the templates can be found to render the settings page.
	 *
	 * @var string
	 */
	private $template_dir;

	/**
	 * The filter repository.
	 *
	 * @var FilterRepository
	 */
	private $filter_repository;

	/**
	 * The post processor repository.
	 *
	 * @var PostProcessorRepository
	 */
	private $post_processor_repository;

	/**
	 * The Antispam Bee configuration object.
	 *
	 * @var AntispamBeeConfig
	 */
	private $config;

	/**
	 * The URL to the plugin.
	 *
	 * @var string
	 */
	private $plugins_url;

	/**
	 * Controller constructor.
	 *
	 * @param FilterRepository        $filter_repository         The filter repository.
	 * @param PostProcessorRepository $post_processor_repository The post processor repository.
	 * @param AntispamBeeConfig       $config                    The Antispam Bee Configuration.
	 * @param string                  $template_dir              The path to the templates directory.
	 * @param string                  $plugins_url               The URL to the plugin.
	 */
	public function __construct(
		FilterRepository $filter_repository,
		PostProcessorRepository $post_processor_repository,
		AntispamBeeConfig $config,
		string $template_dir,
		string $plugins_url
	) {
		$this->template_dir              = $template_dir;
		$this->filter_repository         = $filter_repository;
		$this->post_processor_repository = $post_processor_repository;
		$this->config                    = $config;
		$this->plugins_url               = $plugins_url;
	}

	/**
	 * Registers the settings page.
	 *
	 * @return bool
	 */
	public function register() {
		$this->hook = add_submenu_page(
			'options-general.php',
			'Antispam Bee',
			'Antispam Bee',
			'manage_options',
			'antispam-bee',
			[
				$this,
				'render',
			]
		);

		return false !== $this->hook;
	}

	/**
	 * Renders the settings page.
	 *
	 * @return bool
	 */
	public function render() {

		$this->listen();
		$data             = new \stdClass();
		$data->url        = $this->plugins_url;
		$data->active_tab = 'settings-tab-checks.php';
		$data->menu       = [
			'filters'         => (object) [
				'label'  => __( 'Filter', 'antispam-bee' ),
				'url'    => admin_url( 'options-general.php?page=antispam-bee&tab=filters' ),
				'active' => false,
			],
			'post-processors' => (object) [
				'label'  => __( 'Spam Processing', 'antispam-bee' ),
				'url'    => admin_url( 'options-general.php?page=antispam-bee&tab=post-processors' ),
				'active' => false,
			],
			'advanced'        => (object) [
				'label'  => __( 'Advanced', 'antispam-bee' ),
				'url'    => admin_url( 'options-general.php?page=antispam-bee&tab=advanced' ),
				'active' => false,
			],
		];

		$data->tab = $this->generate_tab_data();

		foreach ( $data->menu as $key => $val ) {
			if ( $key === $this->active_tab() ) {
				$data->menu[ $key ]->active = true;
				$data->active_tab           = 'settings-tab-' . $key . '.php';
			}
		}
		$data->active_tab = $this->template_dir . '/' . $data->active_tab;
		if ( ! is_readable( $this->template_dir . '/settings-wrapper.php' ) ) {
			return false;
		}
		include $this->template_dir . '/settings-wrapper.php';
		return true;
	}

	/**
	 * The settings page has some tabs. This method generates the data for each tab.
	 *
	 * @return object
	 */
	private function generate_tab_data() {
		$tab_data = (object) [
			'nonce'        => wp_create_nonce( 'antispambee-filter' ),
			'nonce_action' => 'antispambee-filter',
			'nonce_name'   => '_antispambee_nonce',
		];
		if ( $this->active_tab() === 'filters' ) {
			$tab_data = $this->filters_tab_data();
		}
		if ( $this->active_tab() === 'post-processors' ) {
			$tab_data = $this->post_processor_tab_data();
		}

		$tab_data->type = $this->active_tab();
		return $tab_data;
	}

	/**
	 * Generates the data for the post processor tab.
	 *
	 * @return object
	 */
	private function post_processor_tab_data() {
		return (object) [
			'nonce'             => wp_create_nonce( 'antispambee-post-processor' ),
			'nonce_action'      => 'antispambee-post-processor',
			'nonce_name'        => '_antispambee_nonce',
			'processors'        => $this->post_processor_repository->registered_processors(),
			'active_processors' => array_map(
				function ( PostProcessorInterface $processor ) : string {
					return $processor->id();
				},
				$this->post_processor_repository->active_processors()
			),
		];
	}

	/**
	 * Generates the data for the filters tab.
	 *
	 * @return object
	 */
	private function filters_tab_data() {
		return (object) [
			'nonce'          => wp_create_nonce( 'antispambee-filter' ),
			'nonce_action'   => 'antispambee-filter',
			'nonce_name'     => '_antispambee_nonce',
			'filters'        => $this->filter_repository->registered_filters(),
			'active_filters' => array_map(
				function ( FilterInterface $filter ) : string {
					return $filter->id();
				},
				$this->filter_repository->active_filters()
			),
		];
	}

	/**
	 * Evaluates, which is the active tab.
	 *
	 * @return string
	 */
	private function active_tab() {
	    // phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		return ( isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'filters'; // Input var okay.
        // phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification
	}

	/**
	 * Listens whether settings need to be saved and saves those.
	 *
	 * @return bool
	 */
	private function listen() {

		$tab_data = $this->generate_tab_data();
		if ( ! isset( $_POST['antispambee_fields'] ) // Input var okay.
			|| ! isset( $_POST['type'] ) // Input var okay.
			|| ! isset( $_POST[ $tab_data->nonce_name ] ) // Input var okay.
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $tab_data->nonce_name ] ) ), $tab_data->nonce_action ) // Input var okay.
		) {
			return false;
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ) ); // Input var okay.
		if ( $type !== $this->active_tab() ) {
			return false;
		}

		$success = true;
		// phpcs:disable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
		foreach ( wp_unslash( $_POST['antispambee_fields'] ) as $raw_key => $raw_value ) { // Input var okay.
			$key   = sanitize_text_field( wp_unslash( $raw_key ) );
			$value = (int) $raw_value;
            // phpcs:enable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
			$result = false;

			if ( 'filters' === $type ) {
				try {
					$filter = $this->filter_repository->from_id( $key );
					$result = ( 1 === $value )
						? $this->config->activate_filter( $filter )
						: $this->config->deactivate_filter( $filter );
				} catch ( Runtime $error ) {
					$result = false;
				}
			}
			if ( 'post-processors' === $type ) {
				try {
					$processor = $this->post_processor_repository->from_id( $key );
					$result    = ( 1 === $value ) ? $this->config->activate_processor( $processor ) : $this->config->deactivate_processor( $processor );
				} catch ( Runtime $error ) {
					$result = false;
				}
			}
			if ( ! $result ) {
				$success = false;
			}
		}

		if ( ! $success ) {
			return false;
		}

		// phpcs:disable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
		// Sanitization happens later through the option.
		if ( isset( $_POST['antispambee_field_config'][ $type ] ) && is_array( $_POST['antispambee_field_config'][ $type ] ) ) {
			$raw_configuration = wp_unslash( $_POST['antispambee_field_config'][ $type ] ); // Input var okay.
			$success           = $this->set_configuration( $raw_configuration, $type );
		}
        // phpcs:disable WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized

		return $success && $this->config->persist();
	}

	/**
	 * Updates configuration data for filters and post processors.
	 *
	 * @param array  $raw_configuration The data to set.
	 * @param string $type              The type of configuration to set.
	 *
	 * @return bool
	 */
	private function set_configuration( array $raw_configuration, string $type ) {
		$success                  = true;
		$entities                 = ( 'filters' === $type ) ? $this->filter_repository->registered_filters() : $this->post_processor_repository->registered_processors();
		$valid_configuration_keys = array_map(
			function ( $with_id ) : string {
				return $with_id->id();
			},
			$entities
		);

		foreach ( $raw_configuration as $key => $value ) {
			if ( ! in_array( $key, $valid_configuration_keys, true ) ) {
				continue;
			}
			if ( ! is_array( $value ) ) {
				continue;
			}

			try {
				$option = ( 'filters' === $type ) ? $this->filter_repository->from_id( $key ) : $this->post_processor_repository->from_id( $key );
				$option = $option->options();
			} catch ( Runtime $error ) {
				continue;
			}
			foreach ( $value as $option_key => $raw_option_value ) {
				if ( ! $option->has( $option_key ) ) {
					continue;
				}
				$option_value = $option->sanitize( $raw_option_value, $option_key );
				$success      = $success && $this->config->has_config( $type ) && $this->config->get_config( $type )->set( $option_key, $option_value );
			}
		}
		return $success;
	}
}
