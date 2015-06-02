<?php
/**
 * Scripts
 *
 * @package     EDD\VeroConnect\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @param       string $hook The slug for the current page
 * @global      string $edd_settings_page The slug for the EDD settings page
 * @return      void
 */
function edd_vero_connect_admin_scripts( $hook ) {
    global $edd_settings_page;

    $admin_pages= array( $edd_settings_page );

    if( in_array( $hook, $admin_pages ) ) {
        wp_enqueue_style( 'edd_vero_connect', EDD_VERO_CONNECT_URL . '/assets/css/admin.css' );
    }
}
add_action( 'admin_enqueue_scripts', 'edd_vero_connect_admin_scripts', 100 );
