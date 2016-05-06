<?php
/**
 * Tracking functions
 *
 * @package     EDD\VeroConnect\Tracking
 * @since       1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add checkbox to checkout page
 *
 * @since       1.0.0
 * @return      void
 */
function edd_vero_connect_add_fields() {
	ob_start();

	if( ! edd_get_option( 'edd_vero_connect_auto_subscribe' ) ) {
		echo '<fieldset id="edd_vero_connect">';
		echo '<label for="edd_vero_connect_signup">';
		echo '<input name="edd_vero_connect_signup" id="edd_vero_connect_signup" type="checkbox" checked="checked" />';
		echo edd_get_option( 'edd_vero_connect_label', __( 'Sign up for our mailing list', 'edd-vero-connect' ) );
		echo '</label>';
		echo '</fieldset>';
	}

	echo ob_get_clean();
}
add_action( 'edd_purchase_form_after_cc_form', 'edd_vero_connect_add_fields', 999 );


/**
 * Check if a user should be subscribed
 *
 * @since       1.0.0
 * @param       array $posted
 * @param       array $user_info The info for this user
 * @param       array $valid_data
 * @return      void
 */
function edd_vero_connect_signup_check( $posted, $user_info, $valid_data ) {
    if( isset( $posted['edd_vero_connect_signup'] ) || edd_get_option( 'edd_vero_connect_auto_subscribe' ) ) {
        $this->subscribe = true;
    }
}
add_action( 'edd_checkout_before_gateway', 'edd_vero_connect_signup_check', 10, 3 );


/**
 * Subscribe an email
 *
 * @since       1.0.0
 * @param       int $payment_id The ID of a given payment
 * @return      void
 */
function edd_vero_connect_signup_user( $payment_id ) {
	if( $this->api ) {
		$payment_meta = edd_get_payment_meta( $payment_id );
		$user_info    = edd_get_payment_meta_user_info( $payment_id );
		$cart_items   = isset( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : false;

		try {
			$this->api->identify(
				$payment_meta['user_info']['id'],
				$payment_meta['user_info']['email'],
				array(
					__( 'First Name', 'edd-vero-connect' ) => $payment_meta['user_info']['first_name'],
					__( 'Last Name', 'edd-vero-connect' )  => $payment_meta['user_info']['last_name']
				)
			);
		} catch( Exception $e ) {
			edd_record_gateway_error( __( 'EDD Vero Connect Error', 'edd-vero-connect' ), print_r( $e->getMessage(), true ) );
			return;
		}

		if( empty( $cart_items ) || ! $cart_items ) {
			$cart_items = maybe_unserialize( $payment_meta['downloads'] );
		}

		if( $cart_items ) {
			foreach( $cart_items as $key => $cart_item ) {
				$item_id = isset( $payment_meta['cart_details'] ) ? $cart_item['id'] : $cart_item;
				$price   = $cart_item['price'];

				try {
					$products = array(
						'product_id'   => $cart_item['id'],
						'product_name' => esc_attr( $cart_item['name'] ),
						'price'        => $price,
						'discount'     => $payment_meta['user_info']['discount']
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
							'id' => $payment_meta['user_info']['id']
						),
						$products
					);
				} catch( Exception $e ) {
					edd_record_gateway_error( __( 'EDD Vero Connect Error', 'edd-vero-connect' ), print_r( $e->getMessage(), true ) );
					return;
				}
			}
		}
	}
}
add_action( 'edd_complete_purchase', 'edd_vero_connect_signup_user', 100, 1 );