<?php
/**
 * Register custom taxonomies for Run Clubs
 */
class NXTRUNN_Taxonomies {
    
    public function __construct() {
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }
    
    public function register_taxonomies() {
        
        // Pace taxonomy
        register_taxonomy( 'run_pace', 'run_club', array(
            'label'        => 'Pace',
            'labels'       => array(
                'name'          => 'Paces',
                'singular_name' => 'Pace',
                'search_items'  => 'Search Paces',
                'all_items'     => 'All Paces',
                'edit_item'     => 'Edit Pace',
                'update_item'   => 'Update Pace',
                'add_new_item'  => 'Add New Pace',
                'new_item_name' => 'New Pace Name',
                'menu_name'     => 'Pace',
            ),
            'hierarchical' => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'query_var'    => true,
            'rewrite'      => array( 'slug' => 'pace' ),
        ));
        
        // Vibe taxonomy
        register_taxonomy( 'run_vibe', 'run_club', array(
            'label'        => 'Vibe',
            'labels'       => array(
                'name'          => 'Vibes',
                'singular_name' => 'Vibe',
                'search_items'  => 'Search Vibes',
                'all_items'     => 'All Vibes',
                'edit_item'     => 'Edit Vibe',
                'update_item'   => 'Update Vibe',
                'add_new_item'  => 'Add New Vibe',
                'new_item_name' => 'New Vibe Name',
                'menu_name'     => 'Vibe',
            ),
            'hierarchical' => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'query_var'    => true,
            'rewrite'      => array( 'slug' => 'vibe' ),
        ));
        
        // Days taxonomy
        register_taxonomy( 'run_days', 'run_club', array(
            'label'        => 'Days',
            'labels'       => array(
                'name'          => 'Days',
                'singular_name' => 'Day',
                'search_items'  => 'Search Days',
                'all_items'     => 'All Days',
                'edit_item'     => 'Edit Day',
                'update_item'   => 'Update Day',
                'add_new_item'  => 'Add New Day',
                'new_item_name' => 'New Day Name',
                'menu_name'     => 'Days',
            ),
            'hierarchical' => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'query_var'    => true,
            'rewrite'      => array( 'slug' => 'days' ),
        ));
        
        // Add default terms
        $this->add_default_terms();
    }
    
    private function add_default_terms() {
        
        // Default paces
        $paces = array( 'All Paces', 'Casual (12+ min/mile)', 'Moderate (9-12 min/mile)', 'Fast (<9 min/mile)' );
        foreach ( $paces as $pace ) {
            if ( ! term_exists( $pace, 'run_pace' ) ) {
                wp_insert_term( $pace, 'run_pace' );
            }
        }
        
        // Default vibes
        $vibes = array( 'Social', 'Competitive', 'Trail Running', 'Road Running', 'Track', 'Beginner-Friendly' );
        foreach ( $vibes as $vibe ) {
            if ( ! term_exists( $vibe, 'run_vibe' ) ) {
                wp_insert_term( $vibe, 'run_vibe' );
            }
        }
        
        // Default days
        $days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
        foreach ( $days as $day ) {
            if ( ! term_exists( $day, 'run_days' ) ) {
                wp_insert_term( $day, 'run_days' );
            }
        }
    }
}