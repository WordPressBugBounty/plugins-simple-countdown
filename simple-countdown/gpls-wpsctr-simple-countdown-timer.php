<?php
namespace GPLSCore\GPLS_PLUGIN_WPSCTR;

/**
 * Plugin Name:     Simple Countdown Timer
 * Description:     Add a countdown to any page or post for a sale, launch or event.
 * Author:          GrandPlugins
 * Author URI:      https://grandplugins.com
 * Text Domain:     simple-countdown
 * Std Name:        gpls-wpsctr-simple-countdown-timer
 * Version:         1.0.6
 *
 * @package         GPLS_Wpsctr_Simple_countdown_Timer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GPLSCore\GPLS_PLUGIN_WPSCTR\Core\Core;
use GPLSCore\GPLS_PLUGIN_WPSCTR\Base;
use function GPLSCore\GPLS_PLUGIN_WPSCTR\Pages\PagesBase\setup_pages;
use function GPLSCore\GPLS_PLUGIN_WPSCTR\Cpts\CptsBase\setup_cpts;
use GPLSCore\GPLS_PLUGIN_WPSCTR\QuickCountDownTimer;

if ( ! class_exists( __NAMESPACE__ . '\GPLS_WPSCTR_Class' ) ) :

	/**
	 * Main Class.
	 */
	class GPLS_WPSCTR_Class {

		/**
		 * Single Instance
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Plugin Info
		 *
		 * @var array
		 */
		private static $plugin_info;

		/**
		 * Core Object
		 *
		 * @return Core
		 */
		private static $core;

		/**
		 * Singular init Function.
		 *
		 * @return Object
		 */
		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Check for Required Plugins before Activate.
		 *
		 * @return void
		 */
		private static function required_plugins_check_activate() {
			if ( empty( self::$plugin_info['required_plugins'] ) ) {
				return;
			}
			foreach ( self::$plugin_info['required_plugins'] as $plugin_basename => $plugin_details ) {
				if ( ! is_plugin_active( $plugin_basename ) ) {
					deactivate_plugins( self::$plugin_info['basename'] );
					wp_die( sprintf( esc_html__( '%1$s ( %2$s ) plugin is required in order to activate the plugin', '%3$s' ), $plugin_details['title'], $plugin_basename, self::$plugin_info['name'] ) );
				}
			}
		}

		/**
		 * Check for Required Plugins before Load.
		 *
		 * @return void
		 */
		private static function required_plugins_check_load() {
			if ( empty( self::$plugin_info['required_plugins'] ) ) {
				return;
			}
			foreach ( self::$plugin_info['required_plugins'] as $plugin_basename => $plugin_details ) {
				if ( ! class_exists( $plugin_details['class_check'] ) ) {
					require_once \ABSPATH . 'wp-admin/includes/plugin.php';
					deactivate_plugins( self::$plugin_info['basename'] );
					return;
				}
			}
		}

		/**
		 * Disable Duplicate Free/Pro.
		 *
		 * @return void
		 */
		private static function disable_duplicate() {
			if ( ! empty( self::$plugin_info['duplicate_base'] ) && is_plugin_active( self::$plugin_info['duplicate_base'] ) ) {
				deactivate_plugins( self::$plugin_info['duplicate_base'] );
			}
		}

		/**
		 * Plugin Activated Hook.
		 *
		 * @return void
		 */
		public static function plugin_activated() {
			self::setup_plugin_info();
			self::required_plugins_check_activate();
			self::disable_duplicate();
			self::includes();
			self::start();
			register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WPSCTR_Class', 'plugin_uninstalled' ) );
			Plugin::activated();
		}

		/**
		 * Base Start.
		 *
		 * @return void
		 */
		private static function start() {
			self::$core = Core::start( self::$plugin_info );
			Base::start( self::$core, self::$plugin_info );
		}

		/**
		 * Plugin Deactivated Hook.
		 *
		 * @return void
		 */
		public static function plugin_deactivated() {
			self::setup_plugin_info();
			self::$core->core_actions( 'deactivated' );
			Plugin::deactivated();
		}

		/**
		 * Plugin Installed hook.
		 *
		 * @return void
		 */
		public static function plugin_uninstalled() {
			self::setup_plugin_info();
			self::$core->core_actions( 'deactivated' );
			Plugin::uninstalled();
		}
		/**
		 * Constructor
		 */
		private function __construct() {
			self::setup_plugin_info();
			$this->load_languages();
			self::includes();
			$this->main_load();
		}

		/**
		 * Includes Files
		 *
		 * @return void
		 */
		public static function includes() {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'vendor/autoload.php';
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/Funnel.php';
		}

		/**
		 * Load languages Folder.
		 *
		 * @return void
		 */
		public function load_languages() {
			load_plugin_textdomain( self::$plugin_info['text_domain'], false, trailingslashit( dirname( self::$plugin_info['basename'] ) ) . 'languages/' );
		}

		/**
		 * Main Load.
		 *
		 * @return void
		 */
		public function main_load() {
			self::required_plugins_check_load();
			self::start();
			$this->load();
		}

		/**
		 * Load CLasses.
		 *
		 * @return void
		 */
		public function load() {
			setup_cpts();
			setup_pages();
			QuickCountDownTimer::init();
			self::funnel();
		}

		/**
		 * Contextual upgrade prompts.
		 *
		 * Both offers are read from the timers this site already has. A
		 * countdown that finished weeks ago and is still sitting on a page is
		 * the most common thing that goes wrong with this plugin, and it is
		 * invisible to the person running the site - they set it once and never
		 * look again.
		 *
		 * @return void
		 */
		private static function funnel() {
			if ( ! class_exists( '\GPLS_Funnel' ) ) {
				return;
			}

			$cpt = \GPLSCore\GPLS_PLUGIN_WPSCTR\Cpts\CountDownTimerCPT::_get_cpt_key();
			$key = self::$plugin_info['name'] . '-countdown-timer-cpt-settings-settings-key';

			/**
			 * Target times of every published timer, as unix timestamps.
			 *
			 * @return array
			 */
			$targets = function () use ( $cpt, $key ) {
				$ids = get_posts(
					array(
						'post_type'        => $cpt,
						'post_status'      => 'publish',
						'numberposts'      => 200,
						'fields'           => 'ids',
						'suppress_filters' => false,
					)
				);

				$out = array();

				foreach ( $ids as $id ) {
					$settings = get_post_meta( $id, $key, true );

					if ( ! is_array( $settings ) || empty( $settings['timer_interval'] ) ) {
						continue;
					}

					$ts = strtotime( $settings['timer_interval'] );

					if ( $ts ) {
						$out[] = $ts;
					}
				}

				return $out;
			};

			\GPLS_Funnel::boot(
				array(
					'slug'       => 'simple-countdown',
					'name'       => 'Simple Countdown Timer',
					'textdomain' => 'simple-countdown',
					'cap'        => 'edit_posts',
					'screens'    => array( 'edit-' . $cpt, $cpt ),
					// A closure, not an array: these strings are translated when the
					// notice renders. Building them here would translate during
					// plugins_loaded, which WordPress 6.7 rightly complains about.
					'offers'     => function () use ( $targets ) {
						return array(
							array(
								'id'      => 'expired_timers',
								'product' => 'simple-countdown-timer',
								'when'    => function () use ( $targets ) {
									$now  = time();
									$done = array_filter( $targets(), function ( $ts ) use ( $now ) { return $ts < $now; } );

									if ( ! $done ) {
										return false;
									}

									return array(
										'expired' => count( $done ),
										'since'   => human_time_diff( max( $done ), $now ),
									);
							},
							'stat'       => '{expired}',
							'stat_label' => esc_html__( 'finished', 'simple-countdown' ),
							'title'      => esc_html__( '{expired} of your timers finished, the most recent {since} ago', 'simple-countdown' ),
							'body'       => esc_html__( 'They are still on the page, sitting at zero. Pro can send visitors somewhere useful the moment a timer ends, or show a sign-up form instead, so a finished countdown stops being a dead end.', 'simple-countdown' ),
							'cta'        => esc_html__( 'See what Pro does', 'simple-countdown' ),
						),
						array(
							'id'      => 'many_timers',
							'product' => 'simple-countdown-timer',
							'when'    => function () use ( $targets ) {
								$all = $targets();

								return count( $all ) >= 3 ? array( 'timers' => count( $all ) ) : false;
							},
							'stat'       => '{timers}',
							'stat_label' => esc_html__( 'timers', 'simple-countdown' ),
							'title'      => esc_html__( 'You are running {timers} timers on this site', 'simple-countdown' ),
							'body'       => esc_html__( 'At that number they rarely all belong in one timezone or one colour scheme. Pro gives each timer its own timezone and its own styling, which matters most when several are running at once.', 'simple-countdown' ),
							'cta'        => esc_html__( 'See what Pro does', 'simple-countdown' ),
						),
						);
					},
				)
			);
		}

		/**
		 * Set Plugin Info
		 *
		 * @return array
		 */
		public static function setup_plugin_info() {
			$plugin_data = get_file_data(
				__FILE__,
				array(
					'Version'     => 'Version',
					'Name'        => 'Plugin Name',
					'URI'         => 'Plugin URI',
					'SName'       => 'Std Name',
					'text_domain' => 'Text Domain',
				),
				false
			);

			self::$plugin_info = array(
				'id'              => 1956,
				'basename'        => plugin_basename( __FILE__ ),
				'version'         => $plugin_data['Version'],
				'name'            => $plugin_data['SName'],
				'text_domain'     => $plugin_data['text_domain'],
				'file'            => __FILE__,
				'plugin_url'      => $plugin_data['URI'],
				'public_name'     => $plugin_data['Name'],
				'path'            => trailingslashit( plugin_dir_path( __FILE__ ) ),
				'url'             => trailingslashit( plugin_dir_url( __FILE__ ) ),
				'options_page'    => $plugin_data['SName'],
				'templates_path'  => trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/Templates/',
				'localize_var'    => str_replace( '-', '_', $plugin_data['SName'] ) . '_localize_data',
				'type'            => 'free',
				'classes_prefix'  => 'gpls-wpsctr',
				'classes_general' => 'gpls-general',
				'duplicate_base'  => 'gpls-wpsctr-simple-countdown-timer/gpls-wpsctr-simple-countdown-timer.php',
				'pro_link'        => 'https://grandplugins.com/product/simple-countdown-timer/?utm_source=free',
				'review_link'     => 'https://wordpress.org/support/plugin/simple-countdown/reviews/#new-post',
			);
		}

	}

	add_action( 'plugins_loaded', array( __NAMESPACE__ . '\GPLS_WPSCTR_Class', 'init' ), 10 );
	register_activation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WPSCTR_Class', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_WPSCTR_Class', 'plugin_deactivated' ) );
endif;
