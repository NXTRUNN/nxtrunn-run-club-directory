<?php
/**
 * Plugin Name:       NXTRUNN Run Club Directory
 * Plugin URI:        https://nxtrunn.com
 * Description:       A comprehensive run club directory with diversity badges, worldwide location search, and admin approval workflow.
 * Version:           1.5.4
 * Author:            NXTRUNN
 * Author URI:        https://nxtrunn.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nxtrunn-run-club
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Plugin version
define( 'NXTRUNN_VERSION', '1.5.4' );
define( 'NXTRUNN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NXTRUNN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NXTRUNN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation hook
 */
function activate_nxtrunn_run_club() {
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-activator.php';
    NXTRUNN_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_nxtrunn_run_club' );

/**
 * Deactivation hook
 */
function deactivate_nxtrunn_run_club() {
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-deactivator.php';
    NXTRUNN_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_nxtrunn_run_club' );

/**
 * Initialize the plugin
 */
function run_nxtrunn_run_club() {
    
    // Load core classes
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-post-type.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-taxonomies.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-meta-boxes.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-geocoding.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-distance.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-badges.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-form-handler.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-email-notifications.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-ajax-handlers.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-claims.php';
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-directory.php';
    
    // Admin classes
    if ( is_admin() ) {
        require_once NXTRUNN_PLUGIN_DIR . 'admin/class-nxtrunn-admin-menu.php';
        require_once NXTRUNN_PLUGIN_DIR . 'admin/class-nxtrunn-admin-settings.php';
        require_once NXTRUNN_PLUGIN_DIR . 'admin/class-nxtrunn-admin-columns.php';
        require_once NXTRUNN_PLUGIN_DIR . 'admin/class-nxtrunn-admin-metabox.php';
        require_once NXTRUNN_PLUGIN_DIR . 'admin/class-nxtrunn-import.php';
    }
    
    // Public classes
    require_once NXTRUNN_PLUGIN_DIR . 'public/class-nxtrunn-shortcodes.php';
    
    // BuddyBoss integration (if active)
    if ( function_exists('bp_is_active') || class_exists('BuddyBoss') ) {
        require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-buddyboss.php';
    }
    
    // Initialize classes
    new NXTRUNN_Post_Type();
    new NXTRUNN_Taxonomies();
    new NXTRUNN_Meta_Boxes();
    new NXTRUNN_Badges();
    new NXTRUNN_Form_Handler();
    new NXTRUNN_AJAX_Handlers();
    new NXTRUNN_Claims();
    new NXTRUNN_Shortcodes();
    
    if ( is_admin() ) {
        new NXTRUNN_Admin_Menu();
        new NXTRUNN_Admin_Settings();
        new NXTRUNN_Admin_Columns();
        new NXTRUNN_Admin_Metabox();
        new NXTRUNN_Import();
    }
    
    // Enqueue assets
    add_action( 'wp_enqueue_scripts', 'nxtrunn_enqueue_public_assets' );
    add_action( 'admin_enqueue_scripts', 'nxtrunn_enqueue_admin_assets' );
}
add_action( 'plugins_loaded', 'run_nxtrunn_run_club' );

/**
 * Enqueue public assets
 */
function nxtrunn_enqueue_public_assets() {
    
    // NEW COACHES CORNER DESIGN CSS
    wp_enqueue_style( 
        'nxtrunn-directory-new', 
        NXTRUNN_PLUGIN_URL . 'public/css/directory-styles-new.css', 
        array(), 
        NXTRUNN_VERSION 
    );
    
    // Form styles (keep existing)
    wp_enqueue_style( 
        'nxtrunn-form', 
        NXTRUNN_PLUGIN_URL . 'public/css/form-styles.css', 
        array(), 
        NXTRUNN_VERSION 
    );
    
    // OLD badges.css REMOVED - now included in directory-styles-new.css
    
    // NEW COACHES CORNER DESIGN JAVASCRIPT
    wp_enqueue_script( 
        'nxtrunn-directory-new', 
        NXTRUNN_PLUGIN_URL . 'public/js/directory-filters-new.js', 
        array('jquery'), 
        NXTRUNN_VERSION, 
        true 
    );
    
    // Form validation (keep existing)
    wp_enqueue_script( 
        'nxtrunn-form', 
        NXTRUNN_PLUGIN_URL . 'public/js/form-validation.js', 
        array('jquery'), 
        NXTRUNN_VERSION, 
        true 
    );
    
    // Localize script for AJAX (IMPORTANT - needed for new design!)
    wp_localize_script( 'nxtrunn-directory-new', 'nxtrunn_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'nxtrunn_nonce' ),
        'is_logged_in' => is_user_logged_in() ? 1 : 0,
        'user_id' => get_current_user_id(),
        'login_url' => wp_login_url(),
        'register_url' => 'https://nxtrunn.com/app/runners-circle-membership/'
    ));
    
    wp_localize_script( 'nxtrunn-form', 'nxtrunn_form_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'nxtrunn_nonce' )
    ));
}

/**
 * Enqueue admin assets
 */
function nxtrunn_enqueue_admin_assets( $hook ) {
    
    // Only load on plugin pages
    if ( 'run_club' !== get_post_type() && strpos( $hook, 'nxtrunn' ) === false ) {
        return;
    }
    
    wp_enqueue_style( 
        'nxtrunn-admin', 
        NXTRUNN_PLUGIN_URL . 'admin/css/admin-styles.css', 
        array(), 
        NXTRUNN_VERSION 
    );
    
    wp_enqueue_script( 
        'nxtrunn-admin', 
        NXTRUNN_PLUGIN_URL . 'admin/js/admin-scripts.js', 
        array('jquery'), 
        NXTRUNN_VERSION, 
        true 
    );
}

/**
 * Load single club template
 */
function nxtrunn_single_club_template( $template ) {
    
    if ( is_singular( 'run_club' ) ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . 'public/templates/single-run-club.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    
    return $template;
}
add_filter( 'template_include', 'nxtrunn_single_club_template', 99 );
