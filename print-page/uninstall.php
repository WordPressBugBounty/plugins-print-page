<?php
/**
 * Print Page Block – Print Full Page or Specific Section Uninstall
 *
 * This file is called when the plugin is deleted from the WordPress admin dashboard.
 * It cleans up all the data stored by the plugin in the database.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if user has enabled this setting
if ( get_option( 'ppb_delete_data_on_uninstall', false ) ) {
	// Delete all print_page custom posts
	$print_page_posts = get_posts( array(
		'post_type'   => 'print_page',
		'numberposts' => -1,
		'post_status' => 'any',
	) );

	if ( ! empty( $print_page_posts ) ) {
		foreach ( $print_page_posts as $post ) {
			wp_delete_post( $post->ID, true ); // Force delete (bypasses trash)
		}
	}

	// Delete any options registered by the plugin in previous versions
	delete_option( 'ppbUtils' );

	// Delete the uninstall option itself
	delete_option( 'ppb_delete_data_on_uninstall' );
}
