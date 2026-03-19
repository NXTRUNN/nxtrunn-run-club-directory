<?php
/**
 * Handle AJAX requests
 */
class NXTRUNN_AJAX_Handlers {
    
    public function __construct() {
        add_action( 'wp_ajax_nxtrunn_search_nearby', array( $this, 'search_nearby_clubs' ) );
        add_action( 'wp_ajax_nopriv_nxtrunn_search_nearby', array( $this, 'search_nearby_clubs' ) );
        add_action( 'wp_ajax_nxtrunn_filter_directory', array( $this, 'filter_directory' ) );
        add_action( 'wp_ajax_nopriv_nxtrunn_filter_directory', array( $this, 'filter_directory' ) );
        add_action( 'wp_ajax_nxtrunn_get_club_details', array( $this, 'get_club_details' ) );
        add_action( 'wp_ajax_nopriv_nxtrunn_get_club_details', array( $this, 'get_club_details' ) );
    }
    
    /**
     * Get single club details for modal
     */
    public function get_club_details() {
        
        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );
        
        $club_id = isset( $_POST['club_id'] ) ? intval( $_POST['club_id'] ) : 0;
        
        if ( ! $club_id ) {
            wp_send_json_error( array( 'message' => 'Invalid club ID' ) );
        }
        
        $club = get_post( $club_id );
        
        if ( ! $club || $club->post_type !== 'run_club' ) {
            wp_send_json_error( array( 'message' => 'Club not found' ) );
        }
        
        $data = $this->format_club_data( $club_id );
        $data['content'] = apply_filters( 'the_content', $club->post_content );
        $data['meta']['meeting_location'] = get_post_meta( $club_id, '_nxtrunn_meeting_location', true );
        $data['meta']['sponsor'] = get_post_meta( $club_id, '_nxtrunn_sponsor', true );
        
        wp_send_json_success( $data );
    }
    
    /**
     * Search for clubs near user's location
     */
    public function search_nearby_clubs() {
        
        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );
        
        $user_lat = floatval( $_POST['lat'] );
        $user_lng = floatval( $_POST['lng'] );
        $radius = isset( $_POST['radius'] ) ? intval( $_POST['radius'] ) : 25;
        $unit = isset( $_POST['unit'] ) ? sanitize_text_field( $_POST['unit'] ) : 'mi';
        
        if ( ! $user_lat || ! $user_lng ) {
            wp_send_json_error( array( 'message' => 'Invalid coordinates' ) );
        }
        
        // Get all published clubs with coordinates
        $args = array(
            'post_type' => 'run_club',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_nxtrunn_latitude',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_nxtrunn_longitude',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $clubs = get_posts( $args );
        $nearby_clubs = array();
        
        foreach ( $clubs as $club ) {
            
            $club_lat = get_post_meta( $club->ID, '_nxtrunn_latitude', true );
            $club_lng = get_post_meta( $club->ID, '_nxtrunn_longitude', true );
            
            if ( $club_lat && $club_lng ) {
                
                $distance = NXTRUNN_Distance::calculate(
                    $user_lat,
                    $user_lng,
                    $club_lat,
                    $club_lng,
                    $unit
                );

                // radius=0 means no cutoff — return all clubs ranked by distance
                if ( $radius === 0 || $distance <= $radius ) {
                    $nearby_clubs[] = $this->format_club_data( $club->ID, $distance, $unit );
                }
            }
        }
        
        // Sort by distance
        usort( $nearby_clubs, function($a, $b) {
            return $a['distance_raw'] <=> $b['distance_raw'];
        });
        
        wp_send_json_success( array(
            'clubs' => $nearby_clubs,
            'count' => count( $nearby_clubs )
        ));
    }
    
    /**
     * Filter directory with various criteria
     */
    public function filter_directory() {
        
        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );
        
        $args = array(
            'post_type' => 'run_club',
            'post_status' => 'publish',
            'posts_per_page' => isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 24,
            'paged' => isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        // Search by club name
        if ( ! empty( $_POST['search'] ) ) {
            $args['s'] = sanitize_text_field( $_POST['search'] );
        }
        
        // Filter by location
        $meta_query = array();
        
        if ( ! empty( $_POST['country'] ) ) {
            $meta_query[] = array(
                'key' => '_nxtrunn_country',
                'value' => sanitize_text_field( $_POST['country'] ),
                'compare' => '='
            );
        }
        
        if ( ! empty( $_POST['state'] ) ) {
            $meta_query[] = array(
                'key' => '_nxtrunn_state',
                'value' => sanitize_text_field( $_POST['state'] ),
                'compare' => '='
            );
        }
        
        if ( ! empty( $_POST['city'] ) ) {
            $meta_query[] = array(
                'key' => '_nxtrunn_city',
                'value' => sanitize_text_field( $_POST['city'] ),
                'compare' => 'LIKE'
            );
        }
        
        // Filter by pace (overlap logic: club min <= user max AND club max >= user min)
        if ( ! empty( $_POST['pace_min'] ) || ! empty( $_POST['pace_max'] ) ) {
            $user_min = isset( $_POST['pace_min'] ) ? intval( $_POST['pace_min'] ) : 300;
            $user_max = isset( $_POST['pace_max'] ) ? intval( $_POST['pace_max'] ) : 1800;

            $meta_query[] = array(
                'key'     => '_nxtrunn_pace_min',
                'value'   => $user_max,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
            $meta_query[] = array(
                'key'     => '_nxtrunn_pace_max',
                'value'   => $user_min,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        // Filter by walker-friendly
        if ( ! empty( $_POST['walker_only'] ) && $_POST['walker_only'] == '1' ) {
            $meta_query[] = array(
                'key'     => '_nxtrunn_walker_friendly',
                'value'   => '1',
                'compare' => '=',
            );
        }

        // Filter by badges - ONLY if explicitly set to 1/true
        if ( isset( $_POST['woman_run'] ) && $_POST['woman_run'] == '1' ) {
            $meta_query[] = array(
                'key' => '_nxtrunn_is_woman_run',
                'value' => '1',
                'compare' => '='
            );
        }
        
        if ( isset( $_POST['bipoc_owned'] ) && $_POST['bipoc_owned'] == '1' ) {
            $meta_query[] = array(
                'key' => '_nxtrunn_is_bipoc_owned',
                'value' => '1',
                'compare' => '='
            );
        }
        
        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }
        
        // Filter by taxonomies
        $tax_query = array();
        
        if ( ! empty( $_POST['pace'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'run_pace',
                'field' => 'slug',
                'terms' => array_map( 'sanitize_text_field', $_POST['pace'] )
            );
        }
        
        if ( ! empty( $_POST['vibe'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'run_vibe',
                'field' => 'slug',
                'terms' => array_map( 'sanitize_text_field', $_POST['vibe'] )
            );
        }
        
        if ( ! empty( $_POST['days'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'run_days',
                'field' => 'slug',
                'terms' => array_map( 'sanitize_text_field', $_POST['days'] )
            );
        }
        
        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }
        
        // Apply filter for custom modifications
        $args = apply_filters( 'nxtrunn_runclub_directory_query_args', $args );
        
        $query = new WP_Query( $args );
        $clubs = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $clubs[] = $this->format_club_data( get_the_ID() );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success( array(
            'clubs' => $clubs,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ));
    }
    
    /**
     * Format club data for JSON response
     */
    private function format_club_data( $post_id, $distance = null, $unit = 'mi' ) {
        
        $pace_terms = wp_get_post_terms( $post_id, 'run_pace', array( 'fields' => 'names' ) );
        $vibe_terms = wp_get_post_terms( $post_id, 'run_vibe', array( 'fields' => 'names' ) );
        $days_terms = wp_get_post_terms( $post_id, 'run_days', array( 'fields' => 'names' ) );
        
        $data = array(
            'id' => $post_id,
            'title' => get_the_title( $post_id ),
            'url' => get_permalink( $post_id ),
            'excerpt' => get_the_excerpt( $post_id ),
            'thumbnail' => get_the_post_thumbnail_url( $post_id, 'medium' ),
            'location' => array(
                'city' => get_post_meta( $post_id, '_nxtrunn_city', true ),
                'state' => get_post_meta( $post_id, '_nxtrunn_state', true ),
                'country' => get_post_meta( $post_id, '_nxtrunn_country', true ),
                'postal_code' => get_post_meta( $post_id, '_nxtrunn_postal_code', true ),
            ),
            'badges' => array(
                'woman_run' => get_post_meta( $post_id, '_nxtrunn_is_woman_run', true ) === '1',
                'bipoc_owned' => get_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', true ) === '1',
            ),
            'badges_html' => NXTRUNN_Badges::display_badges( $post_id ),
            'meta' => array(
                'pace' => ! is_wp_error( $pace_terms ) ? $pace_terms : array(),
                'vibe' => ! is_wp_error( $vibe_terms ) ? $vibe_terms : array(),
                'days' => ! is_wp_error( $days_terms ) ? $days_terms : array(),
                'sponsor' => get_post_meta( $post_id, '_nxtrunn_sponsor', true ),
            ),
            'contact' => array(
                'website' => get_post_meta( $post_id, '_nxtrunn_website', true ),
                'instagram' => get_post_meta( $post_id, '_nxtrunn_instagram', true ),
                'tiktok' => get_post_meta( $post_id, '_nxtrunn_tiktok', true ),
                'strava' => get_post_meta( $post_id, '_nxtrunn_strava', true ),
            ),
            'pace_data' => array(
                'pace_min'         => intval( get_post_meta( $post_id, '_nxtrunn_pace_min', true ) ),
                'pace_max'         => intval( get_post_meta( $post_id, '_nxtrunn_pace_max', true ) ),
                'walker_friendly'  => get_post_meta( $post_id, '_nxtrunn_walker_friendly', true ) === '1',
                'pace_source'      => get_post_meta( $post_id, '_nxtrunn_pace_source', true ),
            ),
            'claim' => array(
                'claimed' => get_post_meta( $post_id, '_nxtrunn_claimed', true ) === '1',
                'is_owner' => is_user_logged_in() && intval( get_post_meta( $post_id, '_nxtrunn_claimed_by', true ) ) === get_current_user_id(),
            )
        );
        
        if ( $distance !== null ) {
            $data['distance'] = NXTRUNN_Distance::format( $distance, $unit );
            $data['distance_raw'] = $distance;
        }
        
        return $data;
    }
}