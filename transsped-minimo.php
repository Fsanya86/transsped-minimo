<?php
/**
 * Plugin Name: Transsped - Minimo
 * Plugin URI: 
 * Description: Transsped - Minimo - Woocommerce integráció
 * Version: 1.0
 * Author: Ferenczi Sándor
 * Author URI: https://sandorferenczi.hu
 * Text Domain: minimo
 * Domain Path: /languages/
 */

// exit if accessed directly
defined( 'ABSPATH' ) or die();

// CLASS ------------------------------------------------------------

class Minimo
{
	function __construct(){}

// Translation ------------------------------------------------------
	
	function textdomain() {
		load_plugin_textdomain( 'minimo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
// Activation -------------------------------------------------------
	function activate() {
		$upload_dir = wp_upload_dir();
		$minimo_stickers_dir = $upload_dir['basedir'].'/minimo-stickers';
		wp_mkdir_p($minimo_stickers_dir);
	}
	function deactivate() {}
	
// Enqueue additional css and javascript files ----------------------

	function register_scripts() {
		add_action('admin_enqueue_scripts',array($this, 'enqueue_admin'));
	}
	
	function enqueue_admin( $hook ) {
		
		global $post;
		if ( $hook == 'woocommerce_page_wc-orders' || $post->post_type == 'shop_order' ) {
			wp_enqueue_script('js-admin', plugin_dir_url( __FILE__ ) .'js/js-admin.js', array('jquery'));
			wp_localize_script( 'js-admin', 'ajax_object_admin', array(
				'ajax_url'	=> admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ajax-nonce' )
			) );
		}
		
		if ( $hook == 'woocommerce_page_wc-settings' ) {
			wp_enqueue_script('js-admin-settings', plugin_dir_url( __FILE__ ) .'js/js-admin-settings.js', array('jquery'));
		}
	}

// Add Settings link to plugin options
	function register_action_links() {
		add_action('plugin_action_links_transsped-minimo/transsped-minimo.php', array($this,'action_links'));
	}
	
	public function action_links( $actions ) {
		$actions['settings'] = '<a href="admin.php?page=wc-settings&tab=transsped">'.esc_html__( 'Beállítások', 'minimo' ).'</a>';
		return $actions;
	}


// Load additional files --------------------------------------------

	function load_files() {
		
		$files_to_load = array (
			'test',						//Testing scripts
			'admin-settings',			//Admin settings
			'get-minimo-response',		//get minimo API response
			'order-actions',			//Order actions
			'order-edit-meta-box',		//Order edit meta box
		);
		
		foreach ($files_to_load as $file_to_load) {
			require_once plugin_dir_path(__FILE__) . 'includes/'.$file_to_load.'.php';
		}
	}

}

// New instance -----------------------------------------------------
if (class_exists('Minimo') ) {
	$minimo = new Minimo();
	$minimo->register_scripts();
	$minimo->register_action_links();
	$minimo->load_files();
}

// Activation -------------------------------------------------------
register_activation_hook( __FILE__,array ($minimo, 'activate') );

// Deactivation -----------------------------------------------------
register_deactivation_hook( __FILE__,array ($minimo, 'deactivate') );