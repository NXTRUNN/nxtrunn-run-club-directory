<?php
/**
 * Register shortcodes
 */
class NXTRUNN_Shortcodes {
    
    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }
    
    public function register_shortcodes() {
        add_shortcode( 'nxtrunn_runclub_directory', array( $this, 'directory_shortcode' ) );
        add_shortcode( 'nxtrunn_runclub_form', array( $this, 'form_shortcode' ) );
    }
    
    /**
     * Directory shortcode
     */
    public function directory_shortcode( $atts ) {
        return NXTRUNN_Directory::render( $atts );
    }
    
    /**
     * Submission form shortcode
     */
    public function form_shortcode( $atts ) {
        
        ob_start();
        ?>
        <div class="nxtrunn-form-wrapper">
            <form id="nxtrunn-submit-form" class="nxtrunn-submit-form" method="post" enctype="multipart/form-data">
                
                <input type="hidden" name="action" value="nxtrunn_submit_club">
                <?php wp_nonce_field( 'nxtrunn_submit_club_nonce', 'nonce' ); ?>
                
                <div class="nxtrunn-form-section">
                    <h3>Club Information</h3>
                    
                    <div class="nxtrunn-form-field">
                        <label for="club_name">Club Name <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                        <input type="text" id="club_name" name="club_name" placeholder="e.g. Black Girls RUN!" required>
                    </div>
                    
                    <div class="nxtrunn-form-field">
                        <label for="description">Description <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                        <textarea id="description" name="description" rows="4" required placeholder="What makes your club special? Share your mission and what new members can expect."></textarea>
                    </div>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Club Logo</h3>
                    <p class="nxtrunn-section-description">Upload your club's logo to make it easily recognizable in the directory.</p>
                    
                    <div class="nxtrunn-form-field">
                        <label for="club_logo">Logo Image (Optional)</label>
                        <input type="file" id="club_logo" name="club_logo" accept="image/*">
                        <p class="nxtrunn-field-help">Recommended: Square image, at least 400x400px. Max file size: 5MB. Formats: JPG, PNG, GIF, WebP.</p>
                        <div id="logo-preview" style="margin-top: 15px; display: none;">
                            <img src="" alt="Logo preview" style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Location</h3>
                    
                    <div class="nxtrunn-form-row">
                        <div class="nxtrunn-form-field">
                            <label for="country">Country <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <?php
                                $countries = $this->get_countries();
                                foreach ( $countries as $code => $name ) {
                                    echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="nxtrunn-form-field">
                            <label for="state">State / Province <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                            <input type="text" id="state" name="state" placeholder="e.g. New York" required>
                        </div>
                    </div>
                    
                    <div class="nxtrunn-form-row">
                        <div class="nxtrunn-form-field">
                            <label for="city">City <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                            <input type="text" id="city" name="city" placeholder="e.g. Brooklyn" required>
                        </div>
                        
                        <div class="nxtrunn-form-field">
                            <label for="postal_code">ZIP / Postal Code <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                            <input type="text" id="postal_code" name="postal_code" placeholder="e.g. 11201" required>
                        </div>
                    </div>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Club Details</h3>
                    
                    <div class="nxtrunn-form-field">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" placeholder="https://yourclub.com">
                    </div>
                    
                    <div class="nxtrunn-form-field">
                        <label for="instagram">Instagram</label>
                        <input type="text" id="instagram" name="instagram" placeholder="@yourclub">
                    </div>
                    
                    <div class="nxtrunn-form-field">
                        <label for="club_sponsor">Sponsor/Partner Brand</label>
                        <select id="club_sponsor" name="club_sponsor">
                            <option value="">No Sponsor</option>
                            <option value="adidas">adidas</option>
                            <option value="altra">Altra</option>
                            <option value="asics">ASICS</option>
                            <option value="bandit">Bandit Running</option>
                            <option value="brooks">Brooks</option>
                            <option value="ciele">Ciele Athletics</option>
                            <option value="craft">Craft</option>
                            <option value="hoka">HOKA</option>
                            <option value="janji">Janji</option>
                            <option value="karhu">Karhu</option>
                            <option value="lululemon">lululemon</option>
                            <option value="mizuno">Mizuno</option>
                            <option value="new-balance">New Balance</option>
                            <option value="newton">Newton Running</option>
                            <option value="nike">Nike</option>
                            <option value="on-running">On Running</option>
                            <option value="puma">PUMA</option>
                            <option value="rabbit">Rabbit</option>
                            <option value="reebok">Reebok</option>
                            <option value="salomon">Salomon</option>
                            <option value="saucony">Saucony</option>
                            <option value="saysky">Saysky</option>
                            <option value="topo">Topo Athletic</option>
                            <option value="tracksmith">Tracksmith</option>
                            <option value="under-armour">Under Armour</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="nxtrunn-form-field" id="other-sponsor-field" style="display: none;">
                        <label for="club_sponsor_other">Other Sponsor Name</label>
                        <input type="text" id="club_sponsor_other" name="club_sponsor_other" placeholder="Enter sponsor name">
                    </div>
                    
                    <div class="nxtrunn-form-field">
                        <label for="contact_email">Contact Email <span class="required" style="color:var(--color-terra,#C86848);">*</span></label>
                        <input type="email" id="contact_email" name="contact_email" placeholder="hello@yourclub.com" required>
                    </div>
                    
                    <div class="nxtrunn-form-field">
                        <label for="meeting_location">Meeting Location</label>
                        <textarea id="meeting_location" name="meeting_location" rows="3" placeholder="Where do you typically meet for runs?"></textarea>
                    </div>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Pace</h3>
                    <?php
                    $paces = get_terms( array( 'taxonomy' => 'run_pace', 'hide_empty' => false ) );
                    if ( ! is_wp_error( $paces ) && ! empty( $paces ) ) {
                        foreach ( $paces as $pace ) {
                            echo '<label class="nxtrunn-checkbox-label">';
                            echo '<input type="checkbox" name="pace[]" value="' . esc_attr( $pace->name ) . '"> ';
                            echo esc_html( $pace->name );
                            echo '</label>';
                        }
                    } else {
                        echo '<p>No pace options available yet.</p>';
                    }
                    ?>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Vibe</h3>
                    <?php
                    $vibes = get_terms( array( 'taxonomy' => 'run_vibe', 'hide_empty' => false ) );
                    if ( ! is_wp_error( $vibes ) && ! empty( $vibes ) ) {
                        foreach ( $vibes as $vibe ) {
                            echo '<label class="nxtrunn-checkbox-label">';
                            echo '<input type="checkbox" name="vibe[]" value="' . esc_attr( $vibe->name ) . '"> ';
                            echo esc_html( $vibe->name );
                            echo '</label>';
                        }
                    } else {
                        echo '<p>No vibe options available yet.</p>';
                    }
                    ?>
                </div>
                
                <div class="nxtrunn-form-section">
                    <h3>Days</h3>
                    <?php
                    $days = get_terms( array( 'taxonomy' => 'run_days', 'hide_empty' => false ) );
                    if ( ! is_wp_error( $days ) && ! empty( $days ) ) {
                        foreach ( $days as $day ) {
                            echo '<label class="nxtrunn-checkbox-label">';
                            echo '<input type="checkbox" name="days[]" value="' . esc_attr( $day->name ) . '"> ';
                            echo esc_html( $day->name );
                            echo '</label>';
                        }
                    } else {
                        echo '<p>No day options available yet.</p>';
                    }
                    ?>
                </div>
                
                <div class="nxtrunn-form-section nxtrunn-badges-section">
                    <h3>Diversity & Inclusion Badges</h3>
                    <p class="nxtrunn-section-description">Select any that apply to your club. These will be verified by our team before publishing.</p>
                    
                    <label class="nxtrunn-checkbox-label">
                        <input type="checkbox" id="is_woman_run" name="is_woman_run" value="1">
                        Woman-Run Club
                        <span class="nxtrunn-field-help">Check this if your club is founded or led by women.</span>
                    </label>
                    
                    <label class="nxtrunn-checkbox-label">
                        <input type="checkbox" id="is_bipoc_owned" name="is_bipoc_owned" value="1">
                        BIPOC-Owned Club
                        <span class="nxtrunn-field-help">Check this if your club is founded or led by BIPOC individuals.</span>
                    </label>
                    
                    <div class="nxtrunn-form-field" id="admin-note-field" style="display: none;">
                        <label for="admin_note">Note to Admin (Optional)</label>
                        <textarea id="admin_note" name="admin_note" rows="4" placeholder="Tell us a bit about your club's leadership to help us verify this badge."></textarea>
                        <p class="nxtrunn-field-help">This helps us verify your badges faster!</p>
                    </div>
                </div>
                
                <div class="nxtrunn-form-message" style="display: none;" role="alert" aria-live="polite"></div>

                <div class="nxtrunn-form-actions">
                    <button type="submit" class="nxtrunn-submit-btn">Submit Your Club</button>
                    <p style="font-size: 0.75rem; color: var(--color-text-disabled, #B5A8BA); margin-top: 12px;">By submitting, you confirm this information is accurate. Clubs are reviewed within 48 hours.</p>
                </div>
                
            </form>
        </div>
        <?php
        
        return ob_get_clean();
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