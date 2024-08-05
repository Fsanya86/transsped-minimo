<?php
defined( 'ABSPATH' ) or die();	// exit if accessed directly

function get_minimo_response( $type, $order_ids = array(), $packages = array() ) {
	
	$is_test = get_option('transsped_test_mode') == 'yes';
	
	$server_base = $is_test ? 
	'https://li1366-20.members.linode.com/api/' :
	'https://minimo-logistics.com/api/';
	
	switch ( $type ) {
		// case 'query_shipments':
			// $startDate = '2023-09-18T09:56:59.893Z';
			// $endDate = '2023-09-25T09:56:59.892Z';
			// $server_url = $server_base."shipments/$startDate/$endDate";
			// break;
		case 'create_shipments':
			if ( empty( $order_ids ) || !is_array( $order_ids ) ) return 'order_ids is a mandatory array';
			if ( count( $order_ids ) > 100 ) return 'max. 100 orders can be sent';
			$server_url = $server_base.'shipments/';
			
			$wc_weight_unit = get_option('woocommerce_weight_unit');
			$weight_conversion_to_kg = array(
				'kg' => 1,
				'g' => 0.001,
				'lbs' => 0.45359237,
				'oz' => 0.0283495231
			);
			
			$postfields = array();
			foreach ( $order_ids as $index => $order_id ) {
				$order = wc_get_order( $order_id );
				//calculate weight
				$total_weight = 0;
				foreach( $order->get_items() as $item_id => $product_item ){
					$quantity = intval( $product_item->get_quantity() );
					$product = $product_item->get_product();
					$product_weight = floatval( $product->get_weight() );
					$total_weight += floatval( $product_weight * $quantity );
				}			
				$total_weight_in_kg = $total_weight * $weight_conversion_to_kg[$wc_weight_unit];
				
				//construct packageList data
				$packageList = array();
				foreach ( $packages[$index] as $package_name => $package_qty ) {
					$packageList[] = array(
						'type' => $package_name,
						'quantity' => $package_qty,
					);
				}
				$customer_address_2 = !empty( $order->get_shipping_address_2() ) ? ' '.$order->get_shipping_address_2() : '';
			
				$postfields[] = array(
					'waybill' => (string) $order_id,
					'speditorName'	=> get_option('transsped_store_name_minimo'),
					'type' => 'Delivery',
					'note' => $order->get_customer_note(),
					'cashOnDelivery' => $order->get_payment_method() == 'cod' ? $order->get_total() : 0,
					'sender' => array(
						'address' => get_option('woocommerce_store_address'),
						'city' => get_option('woocommerce_store_city'),
						'zip' => get_option('woocommerce_store_postcode'),
						'name' => get_option('transsped_store_name'),
						'phoneNumber' => get_option('transsped_store_phone'),
						'smsNumber' => get_option('transsped_store_phone'),
						'email' => get_option('transsped_store_email'),
					),
					'customer' => array(
						'address' => $order->get_shipping_address_1().$customer_address_2,
						'city' => $order->get_shipping_city(),
						'zip' => $order->get_shipping_postcode(),
						'name' => $order->get_formatted_shipping_full_name(),
						'phoneNumber' => !empty( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone(),
						'smsNumber' => !empty( $order->get_shipping_phone() ) ? $order->get_shipping_phone() : $order->get_billing_phone(),
						'email' => $order->get_billing_email(),
					),
					'cargo' => array(
						'packageList' => $packageList,
						'kg' => $total_weight_in_kg,
					),
				);
			}
			break;
			
		case 'download_stickers':
			if ( empty( $order_ids ) || !is_array( $order_ids ) ) return 'order_ids is a mandatory array';
			//check if sticker already exists
			$upload_dir = wp_upload_dir();
			$path = '/minimo-stickers/'.implode('-',$order_ids).'.pdf';
			$filename = $upload_dir['basedir'].$path;
			$url = $upload_dir['baseurl'].$path;
			
			$server_url = $server_base.'download-stickers/';
			
			$items = array();
			
			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				$items[] = array(
					'shipmentId' => $order->get_meta('minimo_shipment_id'),
				);
			}
			
			$postfields = array(
				'type' => get_option('transsped_sticker_type'),
				'items' => $items,
			);
			
			break;
	}
	
	$username = $is_test ? get_option('transsped_api_username_test') : get_option('transsped_api_username');
	$password = $is_test ? get_option('transsped_api_password_test') : get_option('transsped_api_password');
	
	$headers = array(
		"Content-Type: application/json",
		"Accept: application/json",
	);
	
	
	$ch = curl_init();
	
	curl_setopt( $ch, CURLOPT_URL, $server_url );
	curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt( $ch, CURLOPT_USERPWD, $username.':'.$password);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt( $ch, CURLINFO_HEADER_OUT, true);
	if ( isset( $postfields ) ) {
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($postfields));
	}
	
	$response = curl_exec( $ch );
	curl_close($ch);
	
	//logging
	$is_test_string = $is_test ? 'test' : 'live';
	wc_get_logger()->debug(
		$type .' '. $is_test_string . ' request with data: ' . print_r($postfields,true),
		array( 'source'=>'transsped' )
	);
	
	//return
	if ( $type == 'download_stickers' ) {
		if (str_starts_with( $response, '%PDF-' ) ) {
			$result = file_put_contents ($filename, $response);
			wc_get_logger()->debug(
				$result ? 'PDF response saved to '. $url : 'PDF error',
				array('source'=>'transsped')
			);
			return $result ? $url : false;
		} else {
			if ( function_exists('json_validate') ) {
				$log = 'PDF error: '.print_r(json_validate($response) ? json_decode($response) : $response ,true);
			} else {
				$log = !is_null(json_decode($response)) ? json_decode($response) : 'json error: '.$response;
			}
			
			wc_get_logger()->debug( print_r( $log, true) , array('source'=>'transsped')	);
			return ( $log );
		}
		
	} else {
		if ( function_exists('json_validate') ) {
			$log = json_validate($response) ? json_decode($response) : $response;
		} else {
			$log = !is_null(json_decode($response)) ? json_decode($response) : $response;
		}
		wc_get_logger()->debug( 'response: '. print_r($log, true), array('source'=>'transsped')	);
		return ( $log );
	}
}