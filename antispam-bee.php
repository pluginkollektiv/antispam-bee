<?php
/**
 * Plugin Name: Antispam Bee
 * Description: Antispam plugin with a sophisticated toolset for effective day to day comment and trackback spam-fighting. Built with data protection and privacy in mind.
 * Author:      pluginkollektiv
 * Author URI:  https://pluginkollektiv.org
 * Plugin URI:  https://wordpress.org/plugins/antispam-bee/
 * Text Domain: antispam-bee
 * Domain Path: /lang
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version:     3.0-dev
 *
 * @package Antispam Bee
 **/

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee;

use Pluginkollektiv\AntispamBee\Config\AntispamBeeConfig;
use Pluginkollektiv\AntispamBee\Config\GenericWPOption;
use Pluginkollektiv\AntispamBee\Entity\CommentDataFactory;
use Pluginkollektiv\AntispamBee\Exceptions\Runtime;
use Pluginkollektiv\AntispamBee\Filter\FilterFactory;
use Pluginkollektiv\AntispamBee\Filter\FilterInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Handler\CommentSpamHandler;
use Pluginkollektiv\AntispamBee\Helper\IP;
use Pluginkollektiv\AntispamBee\Logger\FileLogger;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorFactory;
use Pluginkollektiv\AntispamBee\PostProcessor\PostProcessorInterface;
use Pluginkollektiv\AntispamBee\Repository\FilterRepository;
use Pluginkollektiv\AntispamBee\Repository\PostProcessorRepository;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;
use Pluginkollektiv\AntispamBee\Settings\Controller;

add_action(
	'plugins_loaded',
	function() {

		global $wpdb;

		if ( ! class_exists( AntispamBeeConfig::class ) ) {
			require_once __DIR__ . '/vendor/autoload.php';
		}
		$option_factory = new OptionFactory();
		$ip             = new IP();
		$filter_factory = new FilterFactory( $ip, $wpdb, $option_factory );

		$log_file               = ( defined( 'ANTISPAM_BEE_LOG_FILE' ) ) ? (string) ANTISPAM_BEE_LOG_FILE : '';
		$logger                 = new FileLogger( $log_file );
		$post_processor_factory = new PostProcessorFactory( $option_factory, $logger );

		$sub_configs = [
			'filters'         => new GenericWPOption( 'antispambee_filter' ),
			'post_processors' => new GenericWPOption( 'antispambee_post_processors' ),
		];
		$raw_options = get_option( 'antispam_bee', [] );
		$config      = new AntispamBeeConfig( $raw_options, 'antispam_bee', $filter_factory, $post_processor_factory, $sub_configs );

		$filter_option_factory    = new OptionFactory( $sub_configs['filters'] );
		$filter_factory           = new FilterFactory( $ip, $wpdb, $filter_option_factory );
		$processor_option_factory = new OptionFactory( $sub_configs['post_processors'] );
		$post_processor_factory   = new PostProcessorFactory( $processor_option_factory, $logger );

		/**
		 * Registers all our filters and post processors.
		 *
		 * What I do not like on this approach: It treats core checks like all other checks.
		 * Maybe we should register core checks in a way, they can't be overwritten
		 * by 3rd parties (see $registrar->register_checks() how you could overwrite a core filter).
		 */
		add_action(
			Registrar::ACTION_ANTISPAMBEE_REGISTER,
			function( Registrar $registrar ) use ( $filter_factory, $config, $post_processor_factory ) {

				$filters = array_map(
					function( string $type ) use ( $filter_factory ) : FilterInterface {
						return $filter_factory->from_id( $type );
					},
					$config->antispambee_filters()
				);
				$registrar->register_filter( ...$filters );

				$post_processors = array_map(
					function( string $id ) use ( $post_processor_factory ) : PostProcessorInterface {
						return $post_processor_factory->from_id( $id );
					},
					$config->antispambee_postprocessor()
				);
				$registrar->register_post_processor( ...$post_processors );
			},
			0
		);
		$registrar = new Registrar( $config );
		$registrar->run();
		$filter_repository         = new FilterRepository( $config, ...$registrar->registered_filters() );
		$post_processor_repository = new PostProcessorRepository( $config, ...$registrar->registered_post_processors() );

		/**
		 * Initializes the admin area.
		 */
		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action(
				'admin_menu',
				function() use ( $filter_repository, $post_processor_repository, $config ) {

					( new Controller(
						$filter_repository,
						$post_processor_repository,
						$config,
						dirname( __FILE__ ) . '/templates/admin',
						plugins_url( '/', __FILE__ )
					) )->register();
				}
			);
		}

		$active_filters = $filter_repository->active_filters();
		/**
		 * Some Checks need preparation, e.g. the Honey pot. Therefore, we have
		 * Preparer, who can prepare/transform whatever they want. Here, we
		 * register them.
		 */
		foreach ( $active_filters as $filter ) {
			$filter->register();
		}

		$comment_data_factory = new CommentDataFactory();
		add_filter(
			'pre_comment_approved',
			function( $approved, $comment ) use ( $filter_repository, $post_processor_repository, $config, $ip, $comment_data_factory ) {
				if ( 'spam' === $approved ) {
					return $approved;
				}

				$comment['comment_author_IP'] = $ip->detect_client_ip( $comment['comment_author_IP'] );
				try {
					$data = $comment_data_factory->get( $comment );
				} catch ( Runtime $error ) {
					if ( defined( 'WP_DEBUG' ) || WP_DEBUG ) {
						throw $error;
					}
					return $approved;
				}

				$reasons = new ReasonsRepository();
				$spam_handler = new CommentSpamHandler( $config, $post_processor_repository );
				$spam_checker = new SpamChecker( $spam_handler, $filter_repository, $reasons );

				return ( $spam_checker->check( $data ) ) ? 'spam' : $approved;
			},
			10,
			2
		);

	}
);
