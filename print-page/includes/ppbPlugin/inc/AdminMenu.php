<?php

namespace PPB;

class AdminMenu  {
    function __construct() {
        add_action('admin_menu', [$this, 'ppb_add_demo_submenu']);
        add_action('admin_head', [$this, 'ppb_admin_menu_color']);

    }

    function ppb_add_demo_submenu(){
        add_submenu_page(
            'edit.php?post_type=print_page',
            'Help & Demos',
            'Help & Demos',
            'manage_options',
            'ppb_demo_page',
            [$this, 'ppb_render_demo_page']
        );
    }

    function ppb_render_demo_page(){
        ?>
            <div
                id='ppbCurrentBplDashboard'
                data-info='<?php echo esc_attr( wp_json_encode( [
                    'version' => PPB_VERSION,
                    'isPremium' => PPBIsPremium(),
                    'hasPro' => PPB_HAS_FRMS,
                    'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
                ] ) ); ?>'
            ></div>
        <?php
    }

    function ppb_admin_menu_color() {
        ?>
        <style>
            #adminmenu a[href="edit.php?post_type=print_page&page=ppb_demo_page"] {
                color: #f18500 !important; 
                font-weight: 600 !important;
            }
        </style>
        <?php
    }

}