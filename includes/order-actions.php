<?php
defined( 'ABSPATH' ) or die();	// exit if accessed directly

add_action ('wp_ajax_get_minimo_sticker','get_minimo_sticker_ajax');

function get_minimo_sticker_ajax() {
	if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
         die ( 'Busted!');
     }
	
	$order_id = $_POST['order_id'];
	$package_types = $_POST;
	unset ($package_types['action']);
	unset ($package_types['order_id']);
	unset ($package_types['nonce']);
	
	foreach ( $package_types as $package_type_name => $value ) {
		if ( $value == '0' ) {
			unset( $package_types[$package_type_name] );
		}
	}
	
	$order = wc_get_order($order_id);
	$minimo_response = array(
		'shipment_created' => 0,
		'sticker_link' => '',
	);
	
	if ( !$order->meta_exists('minimo_shipment_id') || empty( $order->get_meta('minimo_shipment_id') ) ) {
		// meta does not exist - Create shipment
		$response = get_minimo_response( 'create_shipments', array( $order_id ), array( $package_types ) );
		
		// wp_send_json($response);
		
		if ( isset( $response->successCount ) && (string)$response->successCount == '1' ) {
			$minimo_shipment_id = $response->shipments[0]->_id;
			$order->update_meta_data( 'minimo_shipment_id', $minimo_shipment_id );
			$order->save_meta_data();
			$minimo_response['shipment_created']++;
		} elseif ( isset( $response->message ) ) {
			$minimo_response['error_message'] = (string)$response->message;
		} else {
			$minimo_response['error_message'] = (string)$response;
		}		
	}
	
	//Return if error
	if ( isset( $minimo_response['error_message'] ) ) {
		wp_send_json( sprintf( __( 'Hiba a szállítmány létrehozásakor: %s', 'minimo' ), $minimo_response['error_message'] ) );
	}
	
	// We got the shipment ID => create sticker
	$sticker = get_minimo_response( 'download_stickers', array( $order_id ) );
	
	//Return if error
	if ( !$sticker || ( isset( $sticker->success ) && $sticker->success == false ) ) {
		wp_send_json( 'Hiba a címke létrehozásakor: '.$sticker->message );
	}
	
	//We got the sticker
	$minimo_response['sticker_link'] = $sticker;
	$order->add_order_note( __('Transsped címke készült','minimo').' :<a href="'.$sticker.'" target="_blank">'.basename($sticker).'</a>' );
	
	$minimo_response_str = sprintf(__('%d szállítmány létrehozva','minimo'), $minimo_response['shipment_created'] );
	$minimo_response_str.= ', '.__('címke','minimo').': <a href="'.$minimo_response['sticker_link'].'">'.$order_id.'</a>';

	wp_send_json ($minimo_response_str);
}
 