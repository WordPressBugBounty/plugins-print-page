<?php
/**
 * Plugin Name: Print Page Block – Print Full Page or Specific Section
 * Plugin URI: https://bplugins.com/products/print-page/
 * Description: Print the entire page with single click
 * Version: 1.3.3
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
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
    define( 'PPB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.3.3' );
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
    define( 'PPB_DIR_URL', plugin_dir_url( __FILE__ ) );
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
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
		if ( current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=print_page&page=ppb_demo_page' ) ) . '" style="color:#FF7A00;font-weight:bold;">' . esc_html__( 'Help & Demos', 'print-page' ) . '</a>';

			$expiry     = class_exists( '\PPB\Admin\PPB_PromoBanner' ) ? \PPB\Admin\PPB_PromoBanner::EXPIRATION_DATE : '2026-12-05 23:59:59';
			$is_expired = time() > strtotime( $expiry );

			$is_premium = ( function_exists( 'ppbIsPremium' ) && ppbIsPremium() ) || ( function_exists( 'pp_fs' ) && pp_fs()->can_use_premium_code() );

			if ( ! $is_expired && ! $is_premium ) {
				$bfcm_url = 'https://bplugins.com/products/print-page/pricing/?utm_source=admin&utm_medium=plugins&utm_campaign=bfcm2026';
				$links[]  = '<a href="' . esc_url( $bfcm_url ) . '" target="_blank" rel="noopener noreferrer" style="color:#d63638;font-weight:bold;">' . esc_html__( 'Upgrade to Pro & Save 45% This Black Friday!', 'print-page' ) . '</a>';
			}
		}

		return $links;
	} );
}