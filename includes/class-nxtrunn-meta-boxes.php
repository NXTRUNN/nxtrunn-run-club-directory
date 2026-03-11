<?php
/**
 * Register and handle meta boxes for Run Clubs
 */
class NXTRUNN_Meta_Boxes {
    
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_run_club', array( $this, 'save_meta_boxes' ) );
    }
    
    public function add_meta_boxes() {
        
        // Location meta box
        add_meta_box(
            'nxtrunn_location',
            'Location Information',
            array( $this, 'location_meta_box_callback' ),
            'run_club',
            'normal',
            'high'
        );
        
        // Badge meta box
        add_meta_box(
            'nxtrunn_badges',
            'Diversity Badges',
            array( $this, 'badges_meta_box_callback' ),
            'run_club',
            'side',
            'default'
        );
        
        // Club Details meta box
        add_meta_box(
            'nxtrunn_details',
            'Club Details',
            array( $this, 'details_meta_box_callback' ),
            'run_club',
            'normal',
            'default'
        );
    }
    
    public function location_meta_box_callback( $post ) {
        
        wp_nonce_field( 'nxtrunn_location_nonce', 'nxtrunn_location_nonce_field' );
        
        $country = get_post_meta( $post->ID, '_nxtrunn_country', true );
        $state = get_post_meta( $post->ID, '_nxtrunn_state', true );
        $city = get_post_meta( $post->ID, '_nxtrunn_city', true );
        $postal_code = get_post_meta( $post->ID, '_nxtrunn_postal_code', true );
        $street_address = get_post_meta( $post->ID, '_nxtrunn_street_address', true );
        $latitude = get_post_meta( $post->ID, '_nxtrunn_latitude', true );
        $longitude = get_post_meta( $post->ID, '_nxtrunn_longitude', true );
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="nxtrunn_country">Country *</label></th>
                <td>
                    <select name="nxtrunn_country" id="nxtrunn_country" style="width: 100%;" required>
                        <option value="">Select Country</option>
                        <?php
                        $countries = $this->get_countries();
                        foreach ( $countries as $code => $name ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $code ),
                                selected( $country, $code, false ),
                                esc_html( $name )
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="nxtrunn_state">State/Province/Region *</label></th>
                <td><input type="text" name="nxtrunn_state" id="nxtrunn_state" value="<?php echo esc_attr( $state ); ?>" style="width: 100%;" required></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_city">City *</label></th>
                <td><input type="text" name="nxtrunn_city" id="nxtrunn_city" value="<?php echo esc_attr( $city ); ?>" style="width: 100%;" required></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_postal_code">ZIP/Postal Code *</label></th>
                <td><input type="text" name="nxtrunn_postal_code" id="nxtrunn_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" style="width: 100%;" required></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_street_address">Street Address (Optional)</label></th>
                <td><input type="text" name="nxtrunn_street_address" id="nxtrunn_street_address" value="<?php echo esc_attr( $street_address ); ?>" style="width: 100%;"></td>
            </tr>
            <?php if ( $latitude && $longitude ) : ?>
            <tr>
                <th>Coordinates</th>
                <td>
                    <p><strong>Latitude:</strong> <?php echo esc_html( $latitude ); ?></p>
                    <p><strong>Longitude:</strong> <?php echo esc_html( $longitude ); ?></p>
                    <p class="description">These are auto-generated when you save. Update location fields to regenerate.</p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    public function badges_meta_box_callback( $post ) {
        
        wp_nonce_field( 'nxtrunn_badges_nonce', 'nxtrunn_badges_nonce_field' );
        
        $is_woman_run = get_post_meta( $post->ID, '_nxtrunn_is_woman_run', true );
        $is_bipoc_owned = get_post_meta( $post->ID, '_nxtrunn_is_bipoc_owned', true );
        $admin_note = get_post_meta( $post->ID, '_nxtrunn_admin_note', true );
        $needs_verification = get_post_meta( $post->ID, '_nxtrunn_needs_verification', true );
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="nxtrunn_is_woman_run" value="1" <?php checked( $is_woman_run, '1' ); ?>>
                Woman-Run Club
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="nxtrunn_is_bipoc_owned" value="1" <?php checked( $is_bipoc_owned, '1' ); ?>>
                BIPOC-Owned Club
            </label>
        </p>
        
        <?php if ( $admin_note ) : ?>
        <hr>
        <p><strong>Submitter Note:</strong></p>
        <p><?php echo esc_html( $admin_note ); ?></p>
        <?php endif; ?>
        
        <?php if ( $needs_verification ) : ?>
        <hr>
        <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;">
            ⚠️ <strong>Verification Needed</strong><br>
            This club requires badge verification before publishing.
        </p>
        <?php endif; ?>
        <?php
    }
    
    public function details_meta_box_callback( $post ) {
        
        wp_nonce_field( 'nxtrunn_details_nonce', 'nxtrunn_details_nonce_field' );
        
        $website = get_post_meta( $post->ID, '_nxtrunn_website', true );
        $instagram = get_post_meta( $post->ID, '_nxtrunn_instagram', true );
        $tiktok = get_post_meta( $post->ID, '_nxtrunn_tiktok', true );
        $strava = get_post_meta( $post->ID, '_nxtrunn_strava', true );
        $facebook = get_post_meta( $post->ID, '_nxtrunn_facebook', true );
        $contact_email = get_post_meta( $post->ID, '_nxtrunn_contact_email', true );
        $meeting_location = get_post_meta( $post->ID, '_nxtrunn_meeting_location', true );
        $sponsor = get_post_meta( $post->ID, '_nxtrunn_sponsor', true );
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="nxtrunn_website">Website</label></th>
                <td><input type="url" name="nxtrunn_website" id="nxtrunn_website" value="<?php echo esc_url( $website ); ?>" style="width: 100%;" placeholder="https://"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_instagram">Instagram</label></th>
                <td><input type="text" name="nxtrunn_instagram" id="nxtrunn_instagram" value="<?php echo esc_attr( $instagram ); ?>" style="width: 100%;" placeholder="@username"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_tiktok">TikTok</label></th>
                <td><input type="text" name="nxtrunn_tiktok" id="nxtrunn_tiktok" value="<?php echo esc_attr( $tiktok ); ?>" style="width: 100%;" placeholder="@username"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_strava">Strava Club URL</label></th>
                <td><input type="url" name="nxtrunn_strava" id="nxtrunn_strava" value="<?php echo esc_url( $strava ); ?>" style="width: 100%;" placeholder="https://www.strava.com/clubs/clubname"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_facebook">Facebook</label></th>
                <td><input type="url" name="nxtrunn_facebook" id="nxtrunn_facebook" value="<?php echo esc_url( $facebook ); ?>" style="width: 100%;" placeholder="https://facebook.com/page"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_sponsor">Sponsor/Partner Brand</label></th>
                <td><input type="text" name="nxtrunn_sponsor" id="nxtrunn_sponsor" value="<?php echo esc_attr( $sponsor ); ?>" style="width: 100%;" placeholder="e.g., nike, lululemon, brooks">
                <p class="description">Enter the sponsor slug (lowercase, hyphens instead of spaces) or custom sponsor name.</p></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_contact_email">Contact Email</label></th>
                <td><input type="email" name="nxtrunn_contact_email" id="nxtrunn_contact_email" value="<?php echo esc_attr( $contact_email ); ?>" style="width: 100%;"></td>
            </tr>
            <tr>
                <th><label for="nxtrunn_meeting_location">Meeting Location</label></th>
                <td><textarea name="nxtrunn_meeting_location" id="nxtrunn_meeting_location" rows="3" style="width: 100%;"><?php echo esc_textarea( $meeting_location ); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    public function save_meta_boxes( $post_id ) {
        
        // Security checks
        if ( ! isset( $_POST['nxtrunn_location_nonce_field'] ) || 
             ! wp_verify_nonce( $_POST['nxtrunn_location_nonce_field'], 'nxtrunn_location_nonce' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Save location fields
        $location_fields = array( 'country', 'state', 'city', 'postal_code', 'street_address' );
        foreach ( $location_fields as $field ) {
            if ( isset( $_POST['nxtrunn_' . $field] ) ) {
                update_post_meta( $post_id, '_nxtrunn_' . $field, sanitize_text_field( $_POST['nxtrunn_' . $field] ) );
            }
        }
        
        // Save badge fields
        if ( isset( $_POST['nxtrunn_badges_nonce_field'] ) && 
             wp_verify_nonce( $_POST['nxtrunn_badges_nonce_field'], 'nxtrunn_badges_nonce' ) ) {
            
            $is_woman_run = isset( $_POST['nxtrunn_is_woman_run'] ) ? '1' : '0';
            $is_bipoc_owned = isset( $_POST['nxtrunn_is_bipoc_owned'] ) ? '1' : '0';
            
            update_post_meta( $post_id, '_nxtrunn_is_woman_run', $is_woman_run );
            update_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', $is_bipoc_owned );
        }
        
        // Save detail fields
        if ( isset( $_POST['nxtrunn_details_nonce_field'] ) && 
             wp_verify_nonce( $_POST['nxtrunn_details_nonce_field'], 'nxtrunn_details_nonce' ) ) {
            
            update_post_meta( $post_id, '_nxtrunn_website', esc_url_raw( $_POST['nxtrunn_website'] ) );
            update_post_meta( $post_id, '_nxtrunn_instagram', sanitize_text_field( $_POST['nxtrunn_instagram'] ) );
            update_post_meta( $post_id, '_nxtrunn_tiktok', sanitize_text_field( $_POST['nxtrunn_tiktok'] ) );
            update_post_meta( $post_id, '_nxtrunn_strava', esc_url_raw( $_POST['nxtrunn_strava'] ) );
            update_post_meta( $post_id, '_nxtrunn_facebook', esc_url_raw( $_POST['nxtrunn_facebook'] ) );
            update_post_meta( $post_id, '_nxtrunn_contact_email', sanitize_email( $_POST['nxtrunn_contact_email'] ) );
            update_post_meta( $post_id, '_nxtrunn_meeting_location', sanitize_textarea_field( $_POST['nxtrunn_meeting_location'] ) );
            
            // Save sponsor
            if ( isset( $_POST['nxtrunn_sponsor'] ) ) {
                update_post_meta( $post_id, '_nxtrunn_sponsor', sanitize_text_field( $_POST['nxtrunn_sponsor'] ) );
            }
        }
        
        // Geocode if location changed
        if ( isset( $_POST['nxtrunn_city'] ) && isset( $_POST['nxtrunn_country'] ) ) {
            $this->geocode_location( $post_id );
        }
    }
    
    private function geocode_location( $post_id ) {
        
        $geocoder = new NXTRUNN_Geocoding();
        
        $address_parts = array(
            'city' => get_post_meta( $post_id, '_nxtrunn_city', true ),
            'state' => get_post_meta( $post_id, '_nxtrunn_state', true ),
            'country' => get_post_meta( $post_id, '_nxtrunn_country', true ),
            'postal_code' => get_post_meta( $post_id, '_nxtrunn_postal_code', true ),
        );
        
        $coords = $geocoder->geocode_address( $address_parts );
        
        if ( $coords ) {
            update_post_meta( $post_id, '_nxtrunn_latitude', $coords['lat'] );
            update_post_meta( $post_id, '_nxtrunn_longitude', $coords['lng'] );
        }
    }
    
    private function get_countries() {
        return array(
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'IE' => 'Ireland',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'SG' => 'Singapore',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'AR' => 'Argentina',
            'CL' => 'Chile',
            'ZA' => 'South Africa',
            'IN' => 'India',
            'CN' => 'China',
        );
    }
}