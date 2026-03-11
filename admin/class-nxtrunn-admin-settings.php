<?php
/**
 * Register and handle admin settings
 */
class NXTRUNN_Admin_Settings {
    
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    public function register_settings() {
        
        // General settings
        register_setting( 'nxtrunn_settings', 'nxtrunn_auto_approve' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_verify_woman_run' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_verify_bipoc_owned' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_distance_unit' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_clubs_per_page' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_default_radius' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_admin_email' );
        
        // Badge colors
        register_setting( 'nxtrunn_settings', 'nxtrunn_badge_color_woman' );
        register_setting( 'nxtrunn_settings', 'nxtrunn_badge_color_bipoc' );
        
        // Add settings sections
        add_settings_section(
            'nxtrunn_general_section',
            'General Settings',
            array( $this, 'general_section_callback' ),
            'nxtrunn_settings'
        );
        
        add_settings_section(
            'nxtrunn_approval_section',
            'Approval Settings',
            array( $this, 'approval_section_callback' ),
            'nxtrunn_settings'
        );
        
        add_settings_section(
            'nxtrunn_badge_section',
            'Badge Settings',
            array( $this, 'badge_section_callback' ),
            'nxtrunn_settings'
        );
        
        // Add settings fields
        $this->add_general_fields();
        $this->add_approval_fields();
        $this->add_badge_fields();
    }
    
    private function add_general_fields() {
        
        add_settings_field(
            'nxtrunn_distance_unit',
            'Distance Unit',
            array( $this, 'distance_unit_callback' ),
            'nxtrunn_settings',
            'nxtrunn_general_section'
        );
        
        add_settings_field(
            'nxtrunn_clubs_per_page',
            'Clubs Per Page',
            array( $this, 'clubs_per_page_callback' ),
            'nxtrunn_settings',
            'nxtrunn_general_section'
        );
        
        add_settings_field(
            'nxtrunn_default_radius',
            'Default Search Radius',
            array( $this, 'default_radius_callback' ),
            'nxtrunn_settings',
            'nxtrunn_general_section'
        );
    }
    
    private function add_approval_fields() {
        
        add_settings_field(
            'nxtrunn_auto_approve',
            'Auto-Approve Clubs',
            array( $this, 'auto_approve_callback' ),
            'nxtrunn_settings',
            'nxtrunn_approval_section'
        );
        
        add_settings_field(
            'nxtrunn_verify_woman_run',
            'Verify Woman-Run Badge',
            array( $this, 'verify_woman_run_callback' ),
            'nxtrunn_settings',
            'nxtrunn_approval_section'
        );
        
        add_settings_field(
            'nxtrunn_verify_bipoc_owned',
            'Verify BIPOC-Owned Badge',
            array( $this, 'verify_bipoc_owned_callback' ),
            'nxtrunn_settings',
            'nxtrunn_approval_section'
        );
        
        add_settings_field(
            'nxtrunn_admin_email',
            'Admin Notification Email',
            array( $this, 'admin_email_callback' ),
            'nxtrunn_settings',
            'nxtrunn_approval_section'
        );
    }
    
    private function add_badge_fields() {
        
        add_settings_field(
            'nxtrunn_badge_color_woman',
            'Woman-Run Badge Color',
            array( $this, 'badge_color_woman_callback' ),
            'nxtrunn_settings',
            'nxtrunn_badge_section'
        );
        
        add_settings_field(
            'nxtrunn_badge_color_bipoc',
            'BIPOC-Owned Badge Color',
            array( $this, 'badge_color_bipoc_callback' ),
            'nxtrunn_settings',
            'nxtrunn_badge_section'
        );
    }
    
    // Callbacks
    public function general_section_callback() {
        echo '<p>Configure general directory settings.</p>';
    }
    
    public function approval_section_callback() {
        echo '<p>Control how club submissions are approved.</p>';
    }
    
    public function badge_section_callback() {
        echo '<p>Customize diversity badge appearance.</p>';
    }
    
    public function distance_unit_callback() {
        $value = get_option( 'nxtrunn_distance_unit', 'auto' );
        ?>
        <select name="nxtrunn_distance_unit">
            <option value="auto" <?php selected( $value, 'auto' ); ?>>Auto-detect by country</option>
            <option value="mi" <?php selected( $value, 'mi' ); ?>>Miles only</option>
            <option value="km" <?php selected( $value, 'km' ); ?>>Kilometers only</option>
        </select>
        <?php
    }
    
    public function clubs_per_page_callback() {
        $value = get_option( 'nxtrunn_clubs_per_page', 12 );
        echo '<input type="number" name="nxtrunn_clubs_per_page" value="' . esc_attr( $value ) . '" min="1" max="100">';
    }
    
    public function default_radius_callback() {
        $value = get_option( 'nxtrunn_default_radius', 25 );
        echo '<input type="number" name="nxtrunn_default_radius" value="' . esc_attr( $value ) . '" min="1" max="500"> miles/km';
    }
    
    public function auto_approve_callback() {
        $value = get_option( 'nxtrunn_auto_approve', '1' );
        echo '<input type="checkbox" name="nxtrunn_auto_approve" value="1" ' . checked( $value, '1', false ) . '>';
        echo '<p class="description">Auto-approve clubs without diversity badges</p>';
    }
    
    public function verify_woman_run_callback() {
        $value = get_option( 'nxtrunn_verify_woman_run', '1' );
        echo '<input type="checkbox" name="nxtrunn_verify_woman_run" value="1" ' . checked( $value, '1', false ) . '>';
        echo '<p class="description">Require admin verification for Woman-Run badge</p>';
    }
    
    public function verify_bipoc_owned_callback() {
        $value = get_option( 'nxtrunn_verify_bipoc_owned', '1' );
        echo '<input type="checkbox" name="nxtrunn_verify_bipoc_owned" value="1" ' . checked( $value, '1', false ) . '>';
        echo '<p class="description">Require admin verification for BIPOC-Owned badge</p>';
    }
    
    public function admin_email_callback() {
        $value = get_option( 'nxtrunn_admin_email', get_option('admin_email') );
        echo '<input type="email" name="nxtrunn_admin_email" value="' . esc_attr( $value ) . '" class="regular-text">';
    }
    
    public function badge_color_woman_callback() {
        $value = get_option( 'nxtrunn_badge_color_woman', '#d77aa0' );
        echo '<input type="color" name="nxtrunn_badge_color_woman" value="' . esc_attr( $value ) . '">';
    }
    
    public function badge_color_bipoc_callback() {
        $value = get_option( 'nxtrunn_badge_color_bipoc', '#8f657c' );
        echo '<input type="color" name="nxtrunn_badge_color_bipoc" value="' . esc_attr( $value ) . '">';
    }
}