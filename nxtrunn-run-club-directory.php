<?php
/**
 * Plugin Name:       NXTRUNN Run Club Directory
 * Plugin URI:        https://nxtrunn.com
 * Description:       A comprehensive run club directory with diversity badges, worldwide location search, and admin approval workflow.
 * Version:           1.6.1
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
define( 'NXTRUNN_VERSION', '1.6.1' );
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
    require_once NXTRUNN_PLUGIN_DIR . 'includes/class-nxtrunn-outreach-emails.php';
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
 * One-time migration: consolidate legacy sponsor meta keys to _nxtrunn_sponsor
 * Runs once per version bump via option flag.
 */
function nxtrunn_migrate_sponsor_meta() {
    if ( get_option( 'nxtrunn_sponsor_migrated' ) === '1' ) {
        return;
    }

    global $wpdb;

    // Copy _nxtrunn_club_sponsor → _nxtrunn_sponsor where _nxtrunn_sponsor is empty
    $wpdb->query("
        INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
        SELECT pm.post_id, '_nxtrunn_sponsor', pm.meta_value
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->postmeta} existing
            ON existing.post_id = pm.post_id AND existing.meta_key = '_nxtrunn_sponsor'
        WHERE pm.meta_key = '_nxtrunn_club_sponsor'
            AND pm.meta_value != ''
            AND (existing.meta_value IS NULL OR existing.meta_value = '')
    ");

    // Copy club_sponsor → _nxtrunn_sponsor where _nxtrunn_sponsor is still empty
    $wpdb->query("
        INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
        SELECT pm.post_id, '_nxtrunn_sponsor', pm.meta_value
        FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->postmeta} existing
            ON existing.post_id = pm.post_id AND existing.meta_key = '_nxtrunn_sponsor'
        WHERE pm.meta_key = 'club_sponsor'
            AND pm.meta_value != ''
            AND (existing.meta_value IS NULL OR existing.meta_value = '')
    ");

    // Clean up legacy keys
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_nxtrunn_club_sponsor', 'club_sponsor')");

    update_option( 'nxtrunn_sponsor_migrated', '1' );
}
add_action( 'admin_init', 'nxtrunn_migrate_sponsor_meta' );

/**
 * WP-Cron: Send Day-7 follow-up email
 */
add_action( 'nxtrunn_send_followup_email', 'nxtrunn_handle_followup_email' );
function nxtrunn_handle_followup_email( $post_id ) {
    if ( class_exists( 'NXTRUNN_Outreach_Emails' ) ) {
        NXTRUNN_Outreach_Emails::send_followup( $post_id );
    }
}

/**
 * AJAX: Resend outreach email
 */
add_action( 'wp_ajax_nxtrunn_resend_outreach', 'nxtrunn_handle_resend_outreach' );
function nxtrunn_handle_resend_outreach() {
    // Accept either the outreach nonce or the general admin nonce
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nxtrunn_outreach_nonce' ) &&
         ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nxtrunn_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    if ( ! current_user_can( 'edit_posts' ) ) wp_die();

    $post_id = intval( $_POST['post_id'] ?? 0 );
    $email   = get_post_meta( $post_id, '_nxtrunn_outreach_email', true );

    if ( ! $email ) {
        wp_send_json_error( 'No email on file' );
    }

    NXTRUNN_Outreach_Emails::send_outreach( $post_id, $email );
    update_post_meta( $post_id, '_nxtrunn_outreach_sent', time() );

    wp_send_json_success( 'Email resent at ' . date( 'M j, Y g:i a' ) );
}

/**
 * AJAX: Migrate pace data from taxonomy terms to meta fields
 * Maps run_pace taxonomy terms to _nxtrunn_pace_min / _nxtrunn_pace_max meta
 */
add_action( 'wp_ajax_nxtrunn_migrate_pace', 'nxtrunn_handle_pace_migration' );
function nxtrunn_handle_pace_migration() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die();
    check_ajax_referer( 'nxtrunn_nonce', 'nonce' );

    // Pace term → seconds mapping
    $pace_map = array(
        'All Paces'               => array( 'min' => 300, 'max' => 1800, 'walker' => '1' ),
        'Casual (12+ min/mile)'   => array( 'min' => 720, 'max' => 1800, 'walker' => '1' ),
        'Moderate (9-12 min/mile)'=> array( 'min' => 540, 'max' => 720,  'walker' => '0' ),
        'Fast (<9 min/mile)'      => array( 'min' => 300, 'max' => 540,  'walker' => '0' ),
    );

    $clubs = get_posts( array(
        'post_type'      => 'run_club',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );

    $migrated = 0;
    $skipped  = 0;

    foreach ( $clubs as $club_id ) {
        // Skip clubs that already have pace meta set by an owner
        $existing_source = get_post_meta( $club_id, '_nxtrunn_pace_source', true );
        if ( $existing_source === 'owner' ) {
            $skipped++;
            continue;
        }

        // Skip clubs that already have pace meta from a previous migration
        $existing_min = get_post_meta( $club_id, '_nxtrunn_pace_min', true );
        if ( $existing_min && $existing_source === 'migration' ) {
            $skipped++;
            continue;
        }

        $terms = wp_get_post_terms( $club_id, 'run_pace', array( 'fields' => 'names' ) );

        // Clubs with no pace terms get default "All Paces" range
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            if ( ! $existing_min ) {
                update_post_meta( $club_id, '_nxtrunn_pace_min', 300 );
                update_post_meta( $club_id, '_nxtrunn_pace_max', 1800 );
                update_post_meta( $club_id, '_nxtrunn_walker_friendly', '1' );
                update_post_meta( $club_id, '_nxtrunn_pace_source', 'migration' );
                $migrated++;
            }
            continue;
        }

        // Use the most specific term (Fast > Moderate > Casual > All Paces)
        $best = null;
        $priority = array( 'Fast (<9 min/mile)' => 4, 'Moderate (9-12 min/mile)' => 3, 'Casual (12+ min/mile)' => 2, 'All Paces' => 1 );

        foreach ( $terms as $term_name ) {
            if ( isset( $pace_map[ $term_name ] ) ) {
                $p = $priority[ $term_name ] ?? 0;
                if ( $best === null || $p > $priority[ $best ] ) {
                    $best = $term_name;
                }
            }
        }

        // If multiple terms, widen the range (e.g., Casual + Moderate = 540-1800)
        if ( count( $terms ) > 1 ) {
            $min_val = 1800;
            $max_val = 300;
            $has_walker = false;
            foreach ( $terms as $term_name ) {
                if ( isset( $pace_map[ $term_name ] ) ) {
                    $min_val = min( $min_val, $pace_map[ $term_name ]['min'] );
                    $max_val = max( $max_val, $pace_map[ $term_name ]['max'] );
                    if ( $pace_map[ $term_name ]['walker'] === '1' ) $has_walker = true;
                }
            }
            update_post_meta( $club_id, '_nxtrunn_pace_min', $min_val );
            update_post_meta( $club_id, '_nxtrunn_pace_max', $max_val );
            update_post_meta( $club_id, '_nxtrunn_walker_friendly', $has_walker ? '1' : '0' );
            update_post_meta( $club_id, '_nxtrunn_pace_source', 'migration' );
            $migrated++;
        } elseif ( $best && isset( $pace_map[ $best ] ) ) {
            update_post_meta( $club_id, '_nxtrunn_pace_min', $pace_map[ $best ]['min'] );
            update_post_meta( $club_id, '_nxtrunn_pace_max', $pace_map[ $best ]['max'] );
            update_post_meta( $club_id, '_nxtrunn_walker_friendly', $pace_map[ $best ]['walker'] );
            update_post_meta( $club_id, '_nxtrunn_pace_source', 'migration' );
            $migrated++;
        }
    }

    wp_send_json_success( array(
        'migrated' => $migrated,
        'skipped'  => $skipped,
        'total'    => count( $clubs ),
    ) );
}

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
