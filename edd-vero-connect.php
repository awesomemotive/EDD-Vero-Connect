<?php
/**
 * Plugin Name:     Easy Digital Downloads - Vero Connect
 * Plugin URI:      https://wordpress.org/plugins/edd-vero-connect/
 * Description:     Integrate EDD with the Vero email marketing platform
 * Version:         1.0.1
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-vero-connect
 *
 * @package         EDD\VeroConnect
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


if( ! class_exists( 'EDD_Vero_Connect' ) ) {


	/**
	 * Main EDD_Vero_Connect class
	 *
	 * @since       1.0.0
	 */
	class EDD_Vero_Connect {


		/**
		 * @var         EDD_Vero_Connect $instance The one true EDD_Vero_Connect
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * @var         object $api The Vero API object
		 * @since       1.0.0
		 */
		public $api;


		/**
		 * @var         bool $subscribe Whether or not to subscribe a user
		 * @since       1.0.0
		 */
		public $subscribe = false;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true EDD_Vero_Connect
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new EDD_Vero_Connect();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->setup_api();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin version
			define( 'EDD_VERO_CONNECT_VER', '1.0.1' );

			// Plugin path
			define( 'EDD_VERO_CONNECT_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_VERO_CONNECT_URL', plugin_dir_url( __FILE__ ) );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			require_once EDD_VERO_CONNECT_DIR . 'includes/libraries/vero/vero.php';
			require_once EDD_VERO_CONNECT_DIR . 'includes/functions.php';
			require_once EDD_VERO_CONNECT_DIR . 'includes/scripts.php';
			require_once EDD_VERO_CONNECT_DIR . 'includes/tracking.php';

			if( is_admin() ) {
				require_once EDD_VERO_CONNECT_DIR . 'includes/admin/settings/register.php';
			}
		}


		/**
		 * Setup the Vero API
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_api() {
			if( edd_get_option( 'edd_vero_connect_api_mode', 'sandbox' ) == 'sandbox' ) {
				$token = edd_get_option( 'edd_vero_connect_sandbox_auth_token', false );
			} else {
				$token = edd_get_option( 'edd_vero_connect_production_auth_token', false );
			}

			if( $token ) {
				try {
					$this->api = new Vero( $token );
				} catch( Exception $e ) {
					edd_record_gateway_error( __( 'EDD Vero Connect Error', 'edd-vero-connect' ), print_r( $e->getMessage(), true ) );
					return;
				}
			}
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_vero_connect_language_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-vero-connect', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-vero-connect/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-vero-connect/ folder
				load_textdomain( 'edd-vero-connect', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-vero-connect/ folder
				load_textdomain( 'edd-vero-connect', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-vero-connect', false, $lang_dir );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Vero_Connect
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Vero_Connect The one true EDD_Vero_Connect
 */
function edd_vero_connect() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once 'includes/class.s214-edd-activation.php';
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		return EDD_Vero_Connect::instance();
	} else {
		return EDD_Vero_Connect::instance();
	}
}
add_action( 'plugins_loaded', 'edd_vero_connect' );
