<?php
/**
 * Trigger this file on Plugin uninstall
*/

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete generated stickers and its containing folder
require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
$fileSystemDirect = new WP_Filesystem_Direct(false);
$upload_dir = wp_upload_dir();
$minimo_stickers_dir = $upload_dir['basedir'].'/minimo-stickers';
$fileSystemDirect->rmdir($minimo_stickers_dir, true);

//Delete options
global $wpdb;
$options_to_delete = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%transsped%'" );

foreach ( $options_to_delete as $option ) {
	delete_option( $option->option_name );
}