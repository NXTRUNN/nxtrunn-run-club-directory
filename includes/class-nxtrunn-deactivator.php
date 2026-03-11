<?php
/**
 * Fired during plugin deactivation
 */
class NXTRUNN_Deactivator {
    
    public static function deactivate() {
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any transients
        self::clear_transients();
    }
    
    private static function clear_transients() {
        
        global $wpdb;
        
        // Delete all plugin transients
        $wpdb->query( 
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_nxtrunn_%' 
            OR option_name LIKE '_transient_timeout_nxtrunn_%'"
        );
    }
}