<?php
defined( 'ABSPATH' ) or die();	// exit if accessed directly

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

add_action( 'add_meta_boxes', 'admin_order_minimo_metabox', 10, 2 );

function admin_order_minimo_metabox( $post_type, $object ) {
	if ( is_a( $object, 'WC_Order' ) ) {
		$order = $object;
	} elseif ( $post_type == 'shop_order' ) {
		$order = wc_get_order( $object->ID );
	} else {
		return;
	}
	
	
	
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id( 'shop-order' )
        : 'shop_order';
	
	$shipping_list = $order->get_items( 'shipping' );
	if ( empty( $shipping_list ) ) return;
	
	$shipping = reset( $shipping_list );
	$instance_id = $shipping->get_instance_id();
	
	if ( in_array( $instance_id, (array)get_option('transsped_shipping_ids') ) ) {
		add_meta_box( 
			'minimo_order_metabox',
			__('Transsped','minimo'),
			'minimo_meta_box_content',
			$screen,
			'side',
			'high'
		);
	}
}

function minimo_meta_box_content( $object ) {
	$order = is_a( $object, 'WP_Post' ) ? wc_get_order( $object->ID ) : $object;
	
	$package_types = get_option('transsped_package_types');
	
	if ( empty( $package_types ) ) {
		echo sprintf(__('Kérlek állítsd be a csomag típusokat és az alapértelmezett értékeiket a %sbeállításokban%s','minimo'),'<a href="admin.php?page=wc-settings&tab=transsped" target="_blank">','</a>');
		return;
	}
	$package_types = explode( PHP_EOL, $package_types );
	
	foreach ( $package_types as $package_type ){
		$name = strtok($package_type, ',');
		$default_value = trim(strtok(','));
		?>
		<div class="package-type" style="margin:1em 0;">	
			<label for="<?php echo $name ?>" style="display:inline-block;width:20%;"> <?php echo $name; ?></label>
			<input type="button" class="button minus" value="-" />
			<input class="package-type-value" id="<?php echo $name ?>" name="<?php echo $name ?>" value="<?php echo $default_value ?>" size="4" style="text-align:right;height:30px;"/>
			<input type="button" class="button plus" value="+" />
		</div>
		<?php
	}
	?>
	<input type="hidden" class="order_id" name="order_id" value="<?php echo $order->get_id(); ?>" />
	<button class="button transsped_generate_sticker"><?php _e('Címke készítés','minimo'); ?></button>
	<?php
	$upload_dir = wp_upload_dir();
	$path = '/minimo-stickers/'.$order->get_id().'.pdf';
	$filename = $upload_dir['basedir'].$path;
	$url = $upload_dir['baseurl'].$path;
	?>
	<a href="<?php echo $url; ?>" class="button transsped_print_sticker" <?php echo file_exists( $filename ) ? '' : 'disabled' ?>><?php _e('Címke nyomtatás','minimo'); ?></a>

	<span class="spinner"></span>
	<div class="response" style="min-height:3em;margin-top:.5em;"></div>
	
	<?php	
}