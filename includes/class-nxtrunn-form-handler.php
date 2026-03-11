<?php
/**
 * Handle front-end form submissions
 */
class NXTRUNN_Form_Handler {
    
    public function __construct() {
        add_action( 'wp_ajax_nxtrunn_submit_club', array( $this, 'process_submission' ) );
        add_action( 'wp_ajax_nopriv_nxtrunn_submit_club', array( $this, 'process_submission' ) );
    }
    
    public function process_submission() {
        
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nxtrunn_submit_club_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }
        
        // Sanitize inputs
        $club_name = sanitize_text_field( $_POST['club_name'] );
        $description = sanitize_textarea_field( $_POST['description'] );
        $country = sanitize_text_field( $_POST['country'] );
        $state = sanitize_text_field( $_POST['state'] );
        $city = sanitize_text_field( $_POST['city'] );
        $postal_code = sanitize_text_field( $_POST['postal_code'] );
        $website = esc_url_raw( $_POST['website'] );
        $instagram = sanitize_text_field( $_POST['instagram'] );
        $tiktok = isset( $_POST['tiktok'] ) ? sanitize_text_field( $_POST['tiktok'] ) : '';
        $strava = isset( $_POST['strava'] ) ? esc_url_raw( $_POST['strava'] ) : '';
        $contact_email = sanitize_email( $_POST['contact_email'] );
        $meeting_location = sanitize_textarea_field( $_POST['meeting_location'] );
        
        // Badges
        $is_woman_run = isset( $_POST['is_woman_run'] ) ? '1' : '0';
        $is_bipoc_owned = isset( $_POST['is_bipoc_owned'] ) ? '1' : '0';
        $admin_note = sanitize_textarea_field( $_POST['admin_note'] );
        
        // Sponsor
        $sponsor = '';
        if ( ! empty( $_POST['club_sponsor'] ) ) {
            $sponsor = sanitize_text_field( $_POST['club_sponsor'] );
            if ( $sponsor === 'other' && ! empty( $_POST['club_sponsor_other'] ) ) {
                $sponsor = sanitize_text_field( $_POST['club_sponsor_other'] );
            }
        }
        
        // Taxonomies
        $pace = isset( $_POST['pace'] ) ? array_map( 'sanitize_text_field', $_POST['pace'] ) : array();
        $vibe = isset( $_POST['vibe'] ) ? array_map( 'sanitize_text_field', $_POST['vibe'] ) : array();
        $days = isset( $_POST['days'] ) ? array_map( 'sanitize_text_field', $_POST['days'] ) : array();
        
        // Validation
        if ( empty( $club_name ) || empty( $city ) || empty( $country ) ) {
            wp_send_json_error( array( 'message' => 'Please fill in all required fields.' ) );
        }
        
        // Fire pre-submit hook
        do_action( 'nxtrunn_before_runclub_submit', $_POST );
        
        // Determine post status based on badges
        $needs_verification = ( $is_woman_run === '1' || $is_bipoc_owned === '1' );
        $post_status = $needs_verification ? 'pending' : 'publish';
        
        // Create the club post
        $post_id = wp_insert_post( array(
            'post_title'   => $club_name,
            'post_content' => $description,
            'post_type'    => 'run_club',
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
        ));
        
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Failed to create club. Please try again.' ) );
        }
        
        // Handle logo upload
        if ( ! empty( $_FILES['club_logo']['name'] ) ) {
            $this->handle_logo_upload( $post_id, $_FILES['club_logo'] );
        }
        
        // Save meta data
        update_post_meta( $post_id, '_nxtrunn_country', $country );
        update_post_meta( $post_id, '_nxtrunn_state', $state );
        update_post_meta( $post_id, '_nxtrunn_city', $city );
        update_post_meta( $post_id, '_nxtrunn_postal_code', $postal_code );
        update_post_meta( $post_id, '_nxtrunn_website', $website );
        update_post_meta( $post_id, '_nxtrunn_instagram', $instagram );
        update_post_meta( $post_id, '_nxtrunn_tiktok', $tiktok );
        update_post_meta( $post_id, '_nxtrunn_strava', $strava );
        update_post_meta( $post_id, '_nxtrunn_contact_email', $contact_email );
        update_post_meta( $post_id, '_nxtrunn_meeting_location', $meeting_location );
        update_post_meta( $post_id, '_nxtrunn_is_woman_run', $is_woman_run );
        update_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', $is_bipoc_owned );
        update_post_meta( $post_id, '_nxtrunn_admin_note', $admin_note );
        update_post_meta( $post_id, '_nxtrunn_needs_verification', $needs_verification ? '1' : '0' );
        update_post_meta( $post_id, '_nxtrunn_submitter_email', $contact_email );
        
        // Save sponsor
        if ( ! empty( $sponsor ) ) {
            update_post_meta( $post_id, '_nxtrunn_sponsor', $sponsor );
        }
        
        // Set taxonomies
        if ( ! empty( $pace ) ) {
            wp_set_object_terms( $post_id, $pace, 'run_pace' );
        }
        if ( ! empty( $vibe ) ) {
            wp_set_object_terms( $post_id, $vibe, 'run_vibe' );
        }
        if ( ! empty( $days ) ) {
            wp_set_object_terms( $post_id, $days, 'run_days' );
        }
        
        // Geocode the address
        $geocoder = new NXTRUNN_Geocoding();
        $coords = $geocoder->geocode_address( array(
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'postal_code' => $postal_code,
        ));
        
        if ( $coords ) {
            update_post_meta( $post_id, '_nxtrunn_latitude', $coords['lat'] );
            update_post_meta( $post_id, '_nxtrunn_longitude', $coords['lng'] );
        }
        
        // Send emails
        $emailer = new NXTRUNN_Email_Notifications();
        
        if ( $needs_verification ) {
            $emailer->send_admin_verification_email( $post_id );
            $emailer->send_submitter_pending_email( $contact_email, $club_name );
            $message = 'Your club is under review! You\'ll hear from us within 2-3 business days.';
        } else {
            $emailer->send_submitter_approved_email( $contact_email, $club_name, $post_id );
            $message = 'Your club is now live! Thanks for joining NXTRUNN.';
        }
        
        // Fire post-submit hook
        do_action( 'nxtrunn_after_runclub_submit', $post_id, $needs_verification );
        
        wp_send_json_success( array(
            'message' => $message,
            'post_id' => $post_id,
            'needs_verification' => $needs_verification
        ));
    }
    
    /**
     * Handle logo upload
     */
    private function handle_logo_upload( $post_id, $file ) {
        
        // Require WordPress file functions
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        
        // Validate file type
        $allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            return false;
        }
        
        // Validate file size (max 5MB)
        if ( $file['size'] > 5242880 ) {
            return false;
        }
        
        // Upload file
        $upload_overrides = array( 'test_form' => false );
        $uploaded_file = wp_handle_upload( $file, $upload_overrides );
        
        if ( isset( $uploaded_file['error'] ) ) {
            return false;
        }
        
        // Create attachment
        $attachment_data = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name( pathinfo( $uploaded_file['file'], PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment( $attachment_data, $uploaded_file['file'], $post_id );
        
        if ( ! is_wp_error( $attachment_id ) ) {
            // Generate metadata
            $attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
            wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
            
            // Set as featured image
            set_post_thumbnail( $post_id, $attachment_id );
            
            return $attachment_id;
        }
        
        return false;
    }
}