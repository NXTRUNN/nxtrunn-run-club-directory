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
 * AJAX: Save outreach email only (no send)
 */
add_action( 'wp_ajax_nxtrunn_save_outreach_email', 'nxtrunn_handle_save_outreach_email' );
function nxtrunn_handle_save_outreach_email() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nxtrunn_outreach_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    if ( ! current_user_can( 'edit_posts' ) ) wp_die();

    $post_id = absint( $_POST['post_id'] ?? 0 );
    $email   = sanitize_email( $_POST['email'] ?? '' );

    if ( ! $post_id || ! $email ) {
        wp_send_json_error( 'Invalid data' );
    }

    update_post_meta( $post_id, '_nxtrunn_outreach_email', $email );
    wp_send_json_success( 'Email saved' );
}

/**
 * AJAX: Save outreach email + send outreach in one step
 */
add_action( 'wp_ajax_nxtrunn_save_and_send_outreach', 'nxtrunn_handle_save_and_send_outreach' );
function nxtrunn_handle_save_and_send_outreach() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nxtrunn_outreach_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    if ( ! current_user_can( 'edit_posts' ) ) wp_die();

    $post_id = absint( $_POST['post_id'] ?? 0 );
    $email   = sanitize_email( $_POST['email'] ?? '' );

    if ( ! $post_id || ! $email ) {
        wp_send_json_error( 'Invalid data' );
    }

    update_post_meta( $post_id, '_nxtrunn_outreach_email', $email );
    NXTRUNN_Outreach_Emails::send_outreach( $post_id, $email );
    update_post_meta( $post_id, '_nxtrunn_outreach_sent', time() );

    // Schedule follow-up for 7 days later
    if ( ! wp_next_scheduled( 'nxtrunn_send_followup_email', array( $post_id ) ) ) {
        wp_schedule_single_event( time() + ( 7 * DAY_IN_SECONDS ), 'nxtrunn_send_followup_email', array( $post_id ) );
    }

    wp_send_json_success( 'Sent at ' . date( 'M j, Y g:i a' ) );
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

        // Re-migrate clubs with source='migration' (allows re-running with updated logic)
        // Only skip owner-set clubs (handled above)

        // Gather signals from BOTH taxonomies
        $pace_terms = wp_get_post_terms( $club_id, 'run_pace', array( 'fields' => 'names' ) );
        $vibe_terms = wp_get_post_terms( $club_id, 'run_vibe', array( 'fields' => 'names' ) );

        $pace_names = ( ! is_wp_error( $pace_terms ) ) ? $pace_terms : array();
        $vibe_names = ( ! is_wp_error( $vibe_terms ) ) ? $vibe_terms : array();

        // Default: All Paces (widest range)
        $final_min = 300;   // 5:00/mi
        $final_max = 1800;  // 30:00/mi
        $walker = '1';
        $matched = false;

        // 1) Check run_pace taxonomy first (most explicit signal)
        $matched_pace = array();
        foreach ( $pace_names as $term_name ) {
            if ( isset( $pace_map[ $term_name ] ) ) {
                $matched_pace[] = $pace_map[ $term_name ];
            }
        }

        if ( ! empty( $matched_pace ) ) {
            // Widen across all matched pace terms
            $final_min = 1800;
            $final_max = 300;
            $walker = '0';
            foreach ( $matched_pace as $p ) {
                $final_min = min( $final_min, $p['min'] );
                $final_max = max( $final_max, $p['max'] );
                if ( $p['walker'] === '1' ) $walker = '1';
            }
            $matched = true;
        }

        // 2) Check run_vibe taxonomy (Daily.nyc tags stored here)
        //    Beginner-Friendly → casual-to-slow (9:00 – 30:00, walker-friendly)
        //    Competitive       → fast (5:00 – 8:00, no walker)
        //    Both              → full range (5:00 – 30:00)
        if ( ! $matched ) {
            $has_beginner    = in_array( 'Beginner-Friendly', $vibe_names );
            $has_competitive = in_array( 'Competitive', $vibe_names );

            if ( $has_beginner && $has_competitive ) {
                $final_min = 300;   // 5:00/mi — they welcome everyone
                $final_max = 1800;  // 30:00/mi
                $walker = '1';
                $matched = true;
            } elseif ( $has_competitive ) {
                $final_min = 300;   // 5:00/mi
                $final_max = 480;   // 8:00/mi
                $walker = '0';
                $matched = true;
            } elseif ( $has_beginner ) {
                $final_min = 540;   // 9:00/mi
                $final_max = 1800;  // 30:00/mi
                $walker = '1';
                $matched = true;
            }
        }

        // 3) No tags at all — keeps default All Paces (300-1800)

        update_post_meta( $club_id, '_nxtrunn_pace_min', $final_min );
        update_post_meta( $club_id, '_nxtrunn_pace_max', $final_max );
        update_post_meta( $club_id, '_nxtrunn_walker_friendly', $walker );
        update_post_meta( $club_id, '_nxtrunn_pace_source', 'migration' );
        $migrated++;
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
