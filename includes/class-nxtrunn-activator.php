<?php
/**
 * Fired during plugin activation
 */
class NXTRUNN_Activator {
    
    public static function activate() {
        
        // Register post type directly (without needing the class)
        register_post_type( 'run_club', array(
            'public' => true,
            'label'  => 'Run Clubs',
            'rewrite' => array( 'slug' => 'run-clubs' ),
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        ));
        
        // Register taxonomies directly
        register_taxonomy( 'run_pace', 'run_club', array(
            'label' => 'Pace',
            'hierarchical' => false,
            'public' => true,
        ));
        
        register_taxonomy( 'run_vibe', 'run_club', array(
            'label' => 'Vibe',
            'hierarchical' => false,
            'public' => true,
        ));
        
        register_taxonomy( 'run_days', 'run_club', array(
            'label' => 'Days',
            'hierarchical' => false,
            'public' => true,
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        self::set_default_options();
        
        // Check dependencies
        self::check_dependencies();
    }
    
    private static function set_default_options() {
        
        $defaults = array(
            'nxtrunn_auto_approve' => '1',
            'nxtrunn_verify_woman_run' => '1',
            'nxtrunn_verify_bipoc_owned' => '1',
            'nxtrunn_distance_unit' => 'auto',
            'nxtrunn_clubs_per_page' => '12',
            'nxtrunn_default_radius' => '25',
            'nxtrunn_admin_email' => get_option('admin_email'),
            'nxtrunn_badge_color_woman' => '#d77aa0',
            'nxtrunn_badge_color_bipoc' => '#8f657c',
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                add_option( $key, $value );
            }
        }
    }
    
    private static function check_dependencies() {
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( 'NXTRUNN Run Club Directory requires PHP 7.4 or higher.' );
        }
        
        // Check WordPress version
        global $wp_version;
        if ( version_compare( $wp_version, '5.8', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( 'NXTRUNN Run Club Directory requires WordPress 5.8 or higher.' );
        }
    }
}
