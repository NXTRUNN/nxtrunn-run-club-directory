<?php
/**
 * Custom admin columns for run clubs
 */
class NXTRUNN_Admin_Columns {
    
    public function __construct() {
        add_filter( 'manage_run_club_posts_columns', array( $this, 'add_columns' ) );
        add_action( 'manage_run_club_posts_custom_column', array( $this, 'populate_columns' ), 10, 2 );
        add_filter( 'manage_edit-run_club_sortable_columns', array( $this, 'sortable_columns' ) );
    }
    
    public function add_columns( $columns ) {
        
        $new_columns = array();
        
        foreach ( $columns as $key => $title ) {
            $new_columns[$key] = $title;
            
            if ( $key === 'title' ) {
                $new_columns['badges'] = 'Badges';
                $new_columns['location'] = 'Location';
                $new_columns['verification'] = 'Status';
            }
        }
        
        return $new_columns;
    }
    
    public function populate_columns( $column, $post_id ) {
        
        switch ( $column ) {
            case 'badges':
                echo NXTRUNN_Badges::display_badges( $post_id );
                break;
                
            case 'location':
                $city = get_post_meta( $post_id, '_nxtrunn_city', true );
                $state = get_post_meta( $post_id, '_nxtrunn_state', true );
                $country = get_post_meta( $post_id, '_nxtrunn_country', true );
                
                if ( $city && $state ) {
                    echo esc_html( $city . ', ' . $state . ', ' . $country );
                }
                break;
                
            case 'verification':
                $needs_verification = get_post_meta( $post_id, '_nxtrunn_needs_verification', true );
                $post_status = get_post_status( $post_id );
                
                if ( $post_status === 'pending' && $needs_verification === '1' ) {
                    echo '<span style="color: #d63638;">⚠️ Needs Verification</span>';
                } elseif ( $post_status === 'publish' ) {
                    echo '<span style="color: #00a32a;">✅ Published</span>';
                } else {
                    echo '<span style="color: #dba617;">⏸ ' . ucfirst( $post_status ) . '</span>';
                }
                break;
        }
    }
    
    public function sortable_columns( $columns ) {
        $columns['location'] = 'location';
        return $columns;
    }
}