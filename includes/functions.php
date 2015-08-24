<?php
/**
 * Helper functions
 *
 * @package     EDD\VeroConnect\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Check API status
 *
 * @since       1.0.0
 * @return      void
 */
function edd_vero_connect_api_status() {
    $token = edd_get_option( 'edd_vero_connect_auth_token', false );

    if( ! $token ) {
        $status = '<span class="edd-vero-connect-error">' . __( 'Disconnected: Enter Auth Token to continue', 'edd-vero-connect' ) . '</span>';
    }

    echo $status;
}
add_action( 'edd_vero_connect_api_status', 'edd_vero_connect_api_status' );
