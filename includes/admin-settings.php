<?php
defined( 'ABSPATH' ) or die();		// exit if accessed directly

// Add "transsped" as a new tab to woocommerce settings

add_filter( 'woocommerce_settings_tabs_array', 'add_transsped_settings_tabs', 50 );

function add_transsped_settings_tabs( $settings_tabs ) {
	$settings_tabs['transsped'] = __( 'Transsped', 'minimo' );
	return $settings_tabs;
}

add_action( 'woocommerce_settings_tabs_transsped', 'transsped_tab' );

function transsped_tab() {
	woocommerce_admin_fields( get_transsped_tab_settings() );
}

function get_transsped_tab_settings() {
	
	$shipping_instances = array();
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $instance_id => $method ) {
			$method_title = $method->get_title();
			$shipping_instances[$instance_id] = $zone['zone_name'].' -> '.$method_title;
		}
	}
	
	$settings = array(
		'section_title' => array(
			'name'     => __( 'Kapcsolati adatok', 'minimo' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'wc_settings_transsped_section_title'
		),
		
		'transsped_test_mode' => array(
			'name' => __( 'Teszt mód', 'minimo' ),
			'type' => 'checkbox',
			'id'   => 'transsped_test_mode'
		),
		
		'transsped_api_username_test' => array(
			'name' => __( 'Teszt API felhasználónév', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_api_username_test'
		),
		
		'transsped_api_password_test' => array(
			'name' => __( 'Teszt API jelszó', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_api_password_test'
		),
		
		'transsped_api_username' => array(
			'name' => __( 'Éles API felhasználónév', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_api_username'
		),
		
		'transsped_api_password' => array(
			'name' => __( 'Éles API jelszó', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_api_password'
		),
		
		'transsped_store_name' => array(
			'name' => __( 'Bolt neve', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_store_name'
		),
		
		'transsped_store_name_minimo' => array(
			'name' => __( 'Bolt neve minimo rendszerben', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_store_name_minimo'
		),
		
		'transsped_store_phone' => array(
			'name' => __( 'Bolt telefonszáma', 'minimo' ),
			'type' => 'text',
			'id'   => 'transsped_store_phone'
		),
		
		'transsped_store_email' => array(
			'name' => __( 'Ügyfélszolgálat email címe', 'minimo' ),
			'type' => 'email',
			'id'   => 'transsped_store_email'
		),
		
		'transsped_shipping_ids' => array(
			'name' => __('Transsped szállítási módok','minimo'),
			'type' => 'multiselect',
			'options' => $shipping_instances,
			'id' => 'transsped_shipping_ids',
			'desc' => 'A címke készítés box a beállított szállítási módokkal választott rendeléseknél jelenik meg'
		),
		
		'transsped_package_types' => array(
			'name' => __( 'Csomag típusok és alapértelmezett értékeik', 'minimo' ),
			'type' => 'textarea',
			'desc' => __( 'Csomag típusok és alapértelmezett értékeik. A típusok soronként, az alapértelmezett értékek vesszővel elválasztva legyenek, pl.<br/>láda, 1<br/>mini, 0', 'minimo' ),
			'css' => 'height:8em',
			'id'   => 'transsped_package_types'
		),
		
		'transsped_sticker_type' => array(
			'name' => __( 'Címke típus', 'minimo' ),
			'type' => 'select',
			'options' => array(
				'a5' => 'a5',
				'25x10' => '25x10',
				'25x10withRunName' => '25x10withRunName',
				'85x85' => '85x85'				
			),
			'id'   => 'transsped_sticker_type'
		),
		
	);
	
	$settings['section_end'] = array(
		 'type' => 'sectionend',
		 'id' => 'wc_settings_transsped_section_end'
	);
	return apply_filters( 'wc_settings_tab_transsped', $settings );
}

add_action( 'woocommerce_update_options_transsped', 'update_transsped_settings' );

function update_transsped_settings() {
	woocommerce_update_options( get_transsped_tab_settings() );
}