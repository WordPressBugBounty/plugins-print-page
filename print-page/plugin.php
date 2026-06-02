<?php
/**
 * Plugin Name: Print Page Block – Print Full Page or Specific Section
 * Plugin URI: https://bplugins.com/products/print-page/
 * Description: Print the entire page with single click
 * Version: 1.3.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: print-page
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 7.1
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'pp_fs' ) ) {
	pp_fs()->set_basename( true, __FILE__ );
} else {
    define( 'PPB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.3.0' );
    define( 'PPB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'PPB_DIR_PATH', plugin_dir_path( __FILE__ ) );

	if ( ! function_exists( 'pp_fs' ) ) {
		function pp_fs() {
			global $pp_fs;
			
			if ( ! isset( $pp_fs ) ) {

				require_once PPB_DIR_PATH . 'vendor/freemius-lite/start.php';
	
				$pp_fs = fs_lite_dynamic_init( [ 
                    'id'                  => '21137',
                    'slug'                => 'print-page',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_0009bfe45ac358eb763ddf7022975',
                    'is_premium'          => false,
                    'menu'                => array(
                        'slug'           => 'edit.php?post_type=print_page',
                        'first-path'     => 'edit.php?post_type=print_page&page=ppb_demo_page#/welcome',
                        'support'        => false,
                    ),
				] );
			}
	
			return $pp_fs;
		}
		
		pp_fs();
		
		do_action( 'pp_fs_loaded' );
	}

	require_once PPB_DIR_PATH . 'includes/core.php';

	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links ) {
		$help_link = '<a href="' . admin_url( 'edit.php?post_type=print_page&page=ppb_demo_page' ) . '" style="color:#FF7A00;font-weight:bold;">Help & Demos</a>';

		$links[] = $help_link;

		return $links;
	} );
}