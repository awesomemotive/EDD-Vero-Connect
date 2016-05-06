<?php
/**
 * Settings
 *
 * @package     EDD\VeroConnect\Admin\Settings
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       1.0.1
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_vero_connect_add_settings_section( $sections ) {
	$sections['vero-connect'] = __( 'Vero Connect', 'edd-vero-connect' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_vero_connect_add_settings_section' );


/**
 * Add settings
 *
 * @since       1.0.0
 * @param       array $settings The existing plugin settings
 * @return      array The modified plugin settings
 */
function edd_vero_connect_add_settings( $settings ) {
	$new_settings = array(
		'vero-connect' => array(
			array(
				'id'   => 'edd_vero_connect_settings',
				'name' => '<span class="field-section-title">' . __( 'Vero Connect', 'edd-vero-connect' ) . '</span>',
				'desc' => '',
				'type' => 'header'
			),
			array(
				'id'      => 'edd_vero_connect_api_mode',
				'name'    => __( 'API Mode', 'edd-vero-connect' ),
				'desc'    => __( 'Specify whether to use the sandbox or production API', 'edd-vero-connect' ),
				'type'    => 'select',
				'std'     => 'sandbox',
				'options' => array(
					'sandbox'    => __( 'Sandbox', 'edd-vero-connect' ),
					'production' => __( 'Production', 'edd-vero-connect' )
				)
			),
			array(
				'id'   => 'edd_vero_connect_sandbox_auth_token',
				'name' => __( 'Sandbox Auth Token', 'edd-vero-connect' ),
				'desc' => sprintf( __( 'Enter your Vero sandbox auth token (can be found <a href="%s" target="_blank">here</a>)', 'edd-vero-connect' ), 'https://app.getvero.com/settings/project' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_vero_connect_production_auth_token',
				'name' => __( 'Production Auth Token', 'edd-vero-connect' ),
				'desc' => sprintf( __( 'Enter your Vero production auth token (can be found <a href="%s" target="_blank">here</a>)', 'edd-vero-connect' ), 'https://app.getvero.com/settings/project' ),
				'type' => 'text'
			),
			array(
				'id'   => 'edd_vero_connect_auto_subscribe',
				'name' => __( 'Auto Subscribe', 'edd-vero-connect' ),
				'desc' => __( 'Removes the opt-in checkbox and automatically subscribes purchasers', 'edd-vero-connect' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_vero_connect_label',
				'name' => __( 'Checkbox Label', 'edd-vero-connect' ),
				'desc' => __( 'Define a custom label for the subscription checkbox', 'edd-vero-connect' ),
				'type' => 'text',
				'std'  => __( 'Sign up for our mailing list', 'edd-vero-connect' )
			)
		)
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_extensions', 'edd_vero_connect_add_settings' );