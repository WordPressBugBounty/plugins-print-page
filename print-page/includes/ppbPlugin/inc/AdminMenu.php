<?php

namespace PPB;

class AdminMenu  {
    function __construct() {
        add_action('admin_menu', [$this, 'ppb_add_demo_submenu']);
    }

    function ppb_add_demo_submenu(){
        add_submenu_page(
            'edit.php?post_type=print_page',
            'Demo & Help',
            'Demo & Help',
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
                    'hasPro' => PPB_HAS_FRMS
                ] ) ); ?>'
            ></div>
        <?php
    }

}