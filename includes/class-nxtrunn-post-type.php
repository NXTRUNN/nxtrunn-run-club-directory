<?php
/**
 * Register the Run Club custom post type
 */
class NXTRUNN_Post_Type {
    
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
    }
    
    public function register_post_type() {
        
        $labels = array(
            'name'                  => 'Run Clubs',
            'singular_name'         => 'Run Club',
            'menu_name'             => 'Run Clubs',
            'name_admin_bar'        => 'Run Club',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Run Club',
            'new_item'              => 'New Run Club',
            'edit_item'             => 'Edit Run Club',
            'view_item'             => 'View Run Club',
            'all_items'             => 'All Run Clubs',
            'search_items'          => 'Search Run Clubs',
            'parent_item_colon'     => 'Parent Run Clubs:',
            'not_found'             => 'No run clubs found.',
            'not_found_in_trash'    => 'No run clubs found in Trash.',
            'featured_image'        => 'Club Logo',
            'set_featured_image'    => 'Set club logo',
            'remove_featured_image' => 'Remove club logo',
            'use_featured_image'    => 'Use as club logo',
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'run-clubs' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        );
        
        register_post_type( 'run_club', $args );
    }
}