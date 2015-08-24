<?php
/**
 * Plugin Name:     Easy Digital Downloads - Vero Connect
 * Plugin URI:      https://easydigitaldownloads.com/extensions/vero-connect/
 * Description:     Integrate EDD with the Vero email marketing platform
 * Version:         1.0.0
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
                self::$instance->hooks();
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
            define( 'EDD_VERO_CONNECT_VER', '1.0.0' );

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
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Add our extension settings
            add_filter( 'edd_settings_extensions', array( $this, 'add_settings' ) );

            // Add Vero checkbox to checkout page
            add_action( 'edd_purchase_form_after_cc_form', array( $this, 'add_fields' ), 999 );

            // Check if a user should be subscribed
            add_action( 'edd_checkout_before_gateway', array( $this, 'signup_check' ), 10, 3 );

            // Subscribe the user
            add_action( 'edd_complete_purchase', array( $this, 'signup_user' ), 100, 1 );

            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Vero Connect', EDD_VERO_CONNECT_VER, 'Daniel J Griffiths' );
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
                $this->api = new Vero( $token );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing plugin settings
         * @return      array The modified plugin settings
         */
        public function add_settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_vero_connect_settings',
                    'name'  => '<span class="field-section-title">' . __( 'Vero Connect', 'edd-vero-connect' ) . '</span>',
                    'desc'  => '',
                    'type'  => 'header'
                ),
                array(
                    'id'    => 'edd_vero_connect_api_mode',
                    'name'  => __( 'API Mode', 'edd-vero-connect' ),
                    'desc'  => __( 'Specify whether to use the sandbox or production API', 'edd-vero-connect' ),
                    'type'  => 'select',
                    'std'   => 'sandbox',
                    'options'   => array(
                        'sandbox'   => __( 'Sandbox', 'edd-vero-connect' ),
                        'production'=> __( 'Production', 'edd-vero-connect' )
                    )
                ),
                array(
                    'id'    => 'edd_vero_connect_sandbox_auth_token',
                    'name'  => __( 'Sandbox Auth Token', 'edd-vero-connect' ),
                    'desc'  => sprintf( __( 'Enter your Vero sandbox auth token (can be found <a href="%s" target="_blank">here</a>)', 'edd-vero-connect' ), 'https://app.getvero.com/account/api-keys' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_vero_connect_production_auth_token',
                    'name'  => __( 'Production Auth Token', 'edd-vero-connect' ),
                    'desc'  => sprintf( __( 'Enter your Vero production auth token (can be found <a href="%s" target="_blank">here</a>)', 'edd-vero-connect' ), 'https://app.getvero.com/account/api-keys' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_vero_connect_auto_subscribe',
                    'name'  => __( 'Auto Subscribe', 'edd-vero-connect' ),
                    'desc'  => __( 'Removes the opt-in checkbox and automatically subscribes purchasers', 'edd-vero-connect' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'id'    => 'edd_vero_connect_label',
                    'name'  => __( 'Checkbox Label', 'edd-vero-connect' ),
                    'desc'  => __( 'Define a custom label for the subscription checkbox', 'edd-vero-connect' ),
                    'type'  => 'text',
                    'std'   => __( 'Sign up for our mailing list', 'edd-vero-connect' )
                )
            );

            return array_merge( $settings, $new_settings );
        }


        /**
         * Add checkbox to checkout page
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function add_fields() {
            ob_start();

            if( edd_get_option( 'edd_vero_connect_auto_subscribe' ) ) {
                echo '<input name="edd_vero_connect_signup" id="edd_vero_connect_signup" type="hidden" value="true" />';
            } else {
                echo '<fieldset id="edd_vero_connect">';
                echo '<label for="edd_vero_connect_signup">';
                echo '<input name="edd_vero_connect_signup" id="edd_vero_connect_signup" type="checkbox" checked="checked" />';
                echo edd_get_option( 'edd_vero_connect_label', __( 'Sign up for our mailing list', 'edd-vero-connect' ) );
                echo '</label>';
                echo '</fieldset>';
            }

            echo ob_get_clean();
        }


        /**
         * Check if a user should be subscribed
         *
         * @access      public
         * @since       1.0.0
         * @param       array $posted
         * @param       array $user_info The info for this user
         * @param       array $valid_data
         * @return      void
         */
        public function signup_check( $posted, $user_info, $valid_data ) {
            if( isset( $posted['edd_vero_connect_signup'] ) ) {
                $this->subscribe = true;
            }
        }


        /**
         * Subscribe an email
         *
         * @access      public
         * @since       1.0.0
         * @param       int $payment_id The ID of a given payment
         * @return      void
         */
        public function signup_user( $payment_id ) {
            if( $this->api ) {
                $payment_meta   = edd_get_payment_meta( $payment_id );
                $user_info      = edd_get_payment_meta_user_info( $payment_id );
                $cart_items     = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

                try {
                    $this->api->identify(
                        $payment_meta['user_info']['id'],
                        $payment_meta['user_info']['email'],
                        array(
                            __( 'First Name', 'edd-vero-connect' ) => $payment_meta['user_info']['first_name'],
                            __( 'Last Name', 'edd-vero-connect' ) => $payment_meta['user_info']['last_name']
                        )
                    );
                } catch( Exception $e ) {
                    return;
                }

                if( empty( $cart_items ) || ! $cart_items ) {
                    $cart_items = maybe_unserialize( $payment_meta['downloads'] );
                }

                if( $cart_items ) {
                    foreach( $cart_items as $key => $cart_item ) {
                        $item_id        = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
                        $price_override = isset( $payment_meta['cart_details'] ) && ! isset( $payment_meta['subtotal'] ) ? $cart_item['price'] : null;

                        if( $price_override ) {
                            $price = edd_get_download_final_price( $item_id, $user_info, $price_override );
                        } else {
                            $price = $download['price'];
                        }

                        try {
                            $products = array(
                                'product_id'    => $cart_item['id'],
                                'product_name'  => esc_attr( $cart_item['name'] ),
                                'price'         => $price,
                                'discount'      => $payment_meta['user_info']['discount']
                            );

                            if( edd_has_variable_prices( $cart_item['id'] ) ) {
                                $products['price_id']   = $cart_item['item_number']['options']['price_id'];
                                $products['price_name'] = edd_get_price_option_name( $cart_item['id'], $cart_item['item_number']['options']['price_id'] );
                                $products['quantity']   = $cart_item['item_number']['quantity'];
                            } else {
                                $products['quantity'] = $cart_item['quantity'];
                            }

                            $this->api->track(
                                __( 'Purchased Product', 'edd-vero-connect' ),
                                array(
                                    'id'    => $payment_meta['user_info']['id']
                                ),
                                $products
                            );
                        } catch( Exception $e ) {
                            return;
                        }
                    }
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
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-vero-connect/' . $mofile;

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
