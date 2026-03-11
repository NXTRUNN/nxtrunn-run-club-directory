<?php
/**
 * Admin menu pages
 */
class NXTRUNN_Admin_Menu {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
    }
    
    public function add_menu_pages() {
        
        // Settings page
        add_submenu_page(
            'edit.php?post_type=run_club',
            'NXTRUNN Settings',
            'Settings',
            'manage_options',
            'nxtrunn-settings',
            array( $this, 'render_settings_page' )
        );
    }
    
    public function render_settings_page() {
        require_once NXTRUNN_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
}