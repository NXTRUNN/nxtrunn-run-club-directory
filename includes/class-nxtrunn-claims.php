<?php
/**
 * Handle club claim requests and verification
 */
class NXTRUNN_Claims {

    public function __construct() {
        // AJAX: Submit a claim (logged-in only)
        add_action( 'wp_ajax_nxtrunn_submit_claim', array( $this, 'submit_claim' ) );

        // AJAX: Verify claim code (logged-in only)
        add_action( 'wp_ajax_nxtrunn_verify_claim', array( $this, 'verify_claim' ) );

        // AJAX: Save club edits by owner (logged-in only)
        add_action( 'wp_ajax_nxtrunn_save_club_edits', array( $this, 'save_club_edits' ) );

        // After registration redirect with claim param
        add_filter( 'registration_redirect', array( $this, 'claim_redirect_after_register' ), 99 );
        add_filter( 'login_redirect', array( $this, 'claim_redirect_after_login' ), 99, 3 );

        // Show claim banner on MemberPress thank-you pages
        add_action( 'wp_footer', array( $this, 'claim_redirect_banner' ) );
    }

    /**
     * Submit a claim — sends 6-digit code to club email
     */
    public function submit_claim() {

        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to claim a club.' ) );
        }

        $club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
        $club_email = isset( $_POST['club_email'] ) ? sanitize_email( $_POST['club_email'] ) : '';
        $role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';

        if ( ! $club_id || ! $club_email || ! $role ) {
            wp_send_json_error( array( 'message' => 'Please fill in all required fields.' ) );
        }

        $club = get_post( $club_id );
        if ( ! $club || $club->post_type !== 'run_club' ) {
            wp_send_json_error( array( 'message' => 'Club not found.' ) );
        }

        // Check if already claimed
        $claimed_by = get_post_meta( $club_id, '_nxtrunn_claimed_by', true );
        if ( $claimed_by ) {
            wp_send_json_error( array( 'message' => 'This club has already been claimed.' ) );
        }

        // Generate 6-digit code
        $code = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );

        // Store claim data temporarily (expires in 1 hour)
        $user_id = get_current_user_id();
        update_post_meta( $club_id, '_nxtrunn_claim_code', $code );
        update_post_meta( $club_id, '_nxtrunn_claim_code_expires', time() + 3600 );
        update_post_meta( $club_id, '_nxtrunn_claim_pending_user', $user_id );
        update_post_meta( $club_id, '_nxtrunn_claim_pending_email', $club_email );
        update_post_meta( $club_id, '_nxtrunn_claim_pending_role', $role );

        // Track when claim was initiated
        update_post_meta( $club_id, '_nxtrunn_claim_initiated', time() );

        // Send verification email to club email
        $club_name = $club->post_title;
        $subject = 'NXTRUNN — Verify Your Run Club: ' . $club_name;

        $message = sprintf(
            "Hey!\n\n" .
            "Someone is claiming ownership of %s on the NXTRUNN Run Club Directory.\n\n" .
            "Your verification code is:\n\n" .
            "    %s\n\n" .
            "Enter this code on NXTRUNN to verify your club.\n\n" .
            "This code expires in 1 hour.\n\n" .
            "If you didn't request this, you can ignore this email.\n\n" .
            "— NXTRUNN\n" .
            "Built for the Movement.",
            $club_name,
            $code
        );

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        $sent = wp_mail( $club_email, $subject, $message, $headers );

        if ( ! $sent ) {
            wp_send_json_error( array( 'message' => 'Failed to send verification email. Please try again.' ) );
        }

        wp_send_json_success( array(
            'message' => 'Verification code sent to ' . $club_email,
            'club_id' => $club_id
        ));
    }

    /**
     * Verify claim code — auto-approves on correct code
     */
    public function verify_claim() {

        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
        }

        $club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';

        if ( ! $club_id || ! $code ) {
            wp_send_json_error( array( 'message' => 'Please enter the verification code.' ) );
        }

        // Check code
        $stored_code = get_post_meta( $club_id, '_nxtrunn_claim_code', true );
        $expires = get_post_meta( $club_id, '_nxtrunn_claim_code_expires', true );
        $pending_user = get_post_meta( $club_id, '_nxtrunn_claim_pending_user', true );

        if ( ! $stored_code || ! $expires ) {
            wp_send_json_error( array( 'message' => 'No pending claim found. Please start over.' ) );
        }

        // Check expiration
        if ( time() > intval( $expires ) ) {
            // Clean up expired data
            $this->clear_pending_claim( $club_id );
            wp_send_json_error( array( 'message' => 'Code expired. Please submit a new claim.' ) );
        }

        // Check user matches
        $user_id = get_current_user_id();
        if ( intval( $pending_user ) !== $user_id ) {
            wp_send_json_error( array( 'message' => 'This claim belongs to a different user.' ) );
        }

        // Check code matches
        if ( $code !== $stored_code ) {
            wp_send_json_error( array( 'message' => 'Incorrect code. Please try again.' ) );
        }

        // AUTO-APPROVE: Code is correct
        $club_email = get_post_meta( $club_id, '_nxtrunn_claim_pending_email', true );
        $role = get_post_meta( $club_id, '_nxtrunn_claim_pending_role', true );

        update_post_meta( $club_id, '_nxtrunn_claimed', '1' );
        update_post_meta( $club_id, '_nxtrunn_claimed_by', $user_id );
        update_post_meta( $club_id, '_nxtrunn_claimed_date', current_time( 'mysql' ) );
        update_post_meta( $club_id, '_nxtrunn_owner_email', $club_email );
        update_post_meta( $club_id, '_nxtrunn_owner_role', $role );

        // Update club contact email to the verified club email
        update_post_meta( $club_id, '_nxtrunn_contact_email', $club_email );

        // Track claim source — outreach if we sent them an email, organic otherwise
        $outreach_sent = get_post_meta( $club_id, '_nxtrunn_outreach_sent', true );
        $source = $outreach_sent ? 'outreach' : 'organic';
        update_post_meta( $club_id, '_nxtrunn_claim_source', $source );

        // Clean up pending claim data
        $this->clear_pending_claim( $club_id );

        // Fire hook for integrations
        do_action( 'nxtrunn_club_claimed', $club_id, $user_id );

        wp_send_json_success( array(
            'message' => 'Club verified! You now own this listing.',
            'club_id' => $club_id
        ));
    }

    /**
     * Save club edits by verified owner
     */
    public function save_club_edits() {

        check_ajax_referer( 'nxtrunn_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
        }

        $club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
        $user_id = get_current_user_id();

        // Verify ownership
        $owner = get_post_meta( $club_id, '_nxtrunn_claimed_by', true );
        if ( intval( $owner ) !== $user_id && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to edit this club.' ) );
        }

        // Update post content
        if ( isset( $_POST['description'] ) ) {
            wp_update_post( array(
                'ID' => $club_id,
                'post_content' => sanitize_textarea_field( $_POST['description'] )
            ));
        }

        // Update meta fields
        $fields = array(
            'website'          => '_nxtrunn_website',
            'instagram'        => '_nxtrunn_instagram',
            'tiktok'           => '_nxtrunn_tiktok',
            'strava'           => '_nxtrunn_strava',
            'meeting_location' => '_nxtrunn_meeting_location',
            'city'             => '_nxtrunn_city',
            'state'            => '_nxtrunn_state',
            'country'          => '_nxtrunn_country',
        );

        foreach ( $fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( $_POST[ $field ] );
                if ( $field === 'website' || $field === 'strava' ) {
                    $value = esc_url_raw( $_POST[ $field ] );
                }
                update_post_meta( $club_id, $meta_key, $value );
            }
        }

        // Handle logo upload
        if ( ! empty( $_FILES['club_logo']['name'] ) ) {
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
            }

            $allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );
            if ( in_array( $_FILES['club_logo']['type'], $allowed_types ) && $_FILES['club_logo']['size'] <= 5242880 ) {
                $uploaded = wp_handle_upload( $_FILES['club_logo'], array( 'test_form' => false ) );
                if ( ! isset( $uploaded['error'] ) ) {
                    $attachment_data = array(
                        'post_mime_type' => $uploaded['type'],
                        'post_title'     => sanitize_file_name( pathinfo( $uploaded['file'], PATHINFO_FILENAME ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attachment_id = wp_insert_attachment( $attachment_data, $uploaded['file'], $club_id );
                    if ( ! is_wp_error( $attachment_id ) ) {
                        $meta = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
                        wp_update_attachment_metadata( $attachment_id, $meta );
                        set_post_thumbnail( $club_id, $attachment_id );
                    }
                }
            }
        }

        // Re-geocode if location changed
        if ( isset( $_POST['city'] ) || isset( $_POST['state'] ) || isset( $_POST['country'] ) ) {
            $geocoder = new NXTRUNN_Geocoding();
            $coords = $geocoder->geocode_address( array(
                'city'        => get_post_meta( $club_id, '_nxtrunn_city', true ),
                'state'       => get_post_meta( $club_id, '_nxtrunn_state', true ),
                'country'     => get_post_meta( $club_id, '_nxtrunn_country', true ),
                'postal_code' => get_post_meta( $club_id, '_nxtrunn_postal_code', true ),
            ));
            if ( $coords ) {
                update_post_meta( $club_id, '_nxtrunn_latitude', $coords['lat'] );
                update_post_meta( $club_id, '_nxtrunn_longitude', $coords['lng'] );
            }
        }

        wp_send_json_success( array(
            'message' => 'Club updated successfully!',
            'club_id' => $club_id
        ));
    }

    /**
     * Redirect after registration if claim_club param exists
     */
    public function claim_redirect_after_register( $redirect_to ) {
        $club_id = $this->get_claim_club_id();
        if ( $club_id ) {
            $this->clear_claim_cookie();
            $directory_url = $this->get_directory_url();
            return add_query_arg( 'open_claim', $club_id, $directory_url );
        }
        return $redirect_to;
    }

    /**
     * Redirect after login if claim_club param exists
     */
    public function claim_redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
        $club_id = $this->get_claim_club_id();
        if ( $club_id ) {
            $this->clear_claim_cookie();
            $directory_url = $this->get_directory_url();
            return add_query_arg( 'open_claim', $club_id, $directory_url );
        }
        return $redirect_to;
    }

    /**
     * Get claim club ID from cookie or URL param
     */
    private function get_claim_club_id() {
        // Check cookie first (set by JS before redirect to MemberPress)
        if ( isset( $_COOKIE['nxtrunn_claim_club'] ) ) {
            return absint( $_COOKIE['nxtrunn_claim_club'] );
        }
        // Fallback to URL param
        if ( isset( $_REQUEST['claim_club'] ) ) {
            return absint( $_REQUEST['claim_club'] );
        }
        return 0;
    }

    /**
     * Clear the claim cookie after use
     */
    private function clear_claim_cookie() {
        setcookie( 'nxtrunn_claim_club', '', time() - 3600, '/' );
    }

    /**
     * Get the directory page URL (finds the page with the shortcode)
     */
    private function get_directory_url() {
        // Try to find a page with the run club directory shortcode
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_content LIKE '%[nxtrunn_run_club_directory%'
             AND post_status = 'publish'
             AND post_type = 'page'
             LIMIT 1"
        );

        if ( $page_id ) {
            return get_permalink( $page_id );
        }

        // Fallback to known directory URL
        return home_url( '/run-club-directory-2/' );
    }

    /**
     * Show a banner on any page if claim cookie exists and user is logged in.
     * Fires on MemberPress thank-you page, /app/ homepage, etc.
     */
    public function claim_redirect_banner() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        if ( ! isset( $_COOKIE['nxtrunn_claim_club'] ) ) {
            return;
        }

        $directory_url = $this->get_directory_url();
        ?>
        <div id="nxtrunn-claim-banner" style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            background: linear-gradient(135deg, #5E9070 0%, #4a7a5c 100%);
            color: #fff;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        ">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;flex-shrink:0;opacity:0.9;">
                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>You're almost there! Head to the <strong>Run Club Directory</strong> to finish claiming your club.</span>
            <a href="<?php echo esc_url( $directory_url ); ?>" style="
                background: #fff;
                color: #5E9070;
                padding: 8px 20px;
                border-radius: 9999px;
                font-weight: 700;
                font-family: 'Barlow Condensed', sans-serif;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 13px;
                text-decoration: none;
                white-space: nowrap;
                flex-shrink: 0;
            ">Go to Directory</a>
            <button onclick="this.parentElement.remove();" style="
                background: none;
                border: none;
                color: rgba(255,255,255,0.7);
                cursor: pointer;
                font-size: 20px;
                line-height: 1;
                padding: 4px;
                flex-shrink: 0;
            ">&times;</button>
        </div>
        <?php
    }

    /**
     * Clear pending claim data
     */
    private function clear_pending_claim( $club_id ) {
        delete_post_meta( $club_id, '_nxtrunn_claim_code' );
        delete_post_meta( $club_id, '_nxtrunn_claim_code_expires' );
        delete_post_meta( $club_id, '_nxtrunn_claim_pending_user' );
        delete_post_meta( $club_id, '_nxtrunn_claim_pending_email' );
        delete_post_meta( $club_id, '_nxtrunn_claim_pending_role' );
    }
}