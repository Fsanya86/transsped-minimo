<?php
// exit if accessed directly
defined( 'ABSPATH' ) or die();

/* Test scripts on test page ----------------------------------------------------------------------*/

function minimo_test() {
	
	print_r ("testing\n") ;
	global $wpdb;
	$options = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%transsped_test_mode%'" );
	
	print_r ($options);
}





	
function minimo_test_filter() {
	if ( is_page('minimo-test') && !is_admin() ) {
		show_admin_bar(false);
		echo '<pre>';
		minimo_test();
		echo '</pre>';
	}
}
add_action ('wp','minimo_test_filter');