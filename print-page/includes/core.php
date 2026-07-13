<?php

if (!defined('ABSPATH')) exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if( !class_exists( 'PPBPlugin' ) ){
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
    class PPBPlugin{
        function __construct(){
            $this -> loaded_classes();
        }
 
        function loaded_classes(){
			require_once PPB_DIR_PATH . 'includes/Init.php';
			require_once PPB_DIR_PATH . 'includes/AdminMenu.php';
			require_once PPB_DIR_PATH . 'includes/Enqueue.php';
			require_once PPB_DIR_PATH . 'includes/ShortCode.php';
			require_once PPB_DIR_PATH . 'includes/CustomColumn.php';
			require_once PPB_DIR_PATH . 'includes/Ajax.php';

			new PrintPage\Init();
			new PrintPage\AdminMenu();
			new PrintPage\Enqueue();
			new PrintPage\ShortCode();
			new PrintPage\CustomColumn();
			new PrintPage\Ajax();
		}
        
    }
    new PPBPlugin();
}