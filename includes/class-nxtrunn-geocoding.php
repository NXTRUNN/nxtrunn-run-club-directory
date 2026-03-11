<?php
/**
 * Handle geocoding via Nominatim (OpenStreetMap)
 */
class NXTRUNN_Geocoding {
    
    public function geocode_address( $address_parts ) {
        
        // Build query string
        $query = sprintf(
            '%s, %s, %s, %s',
            $address_parts['city'],
            $address_parts['state'],
            $address_parts['postal_code'],
            $address_parts['country']
        );
        
        // Check transient cache first
        $cache_key = 'nxtrunn_geocode_' . md5( $query );
        $cached = get_transient( $cache_key );
        
        if ( $cached !== false ) {
            return $cached;
        }
        
        // Nominatim API
        $url = sprintf(
            'https://nominatim.openstreetmap.org/search?q=%s&format=json&limit=1',
            urlencode( $query )
        );
        
        // Make request with proper headers
        $response = wp_remote_get( $url, array(
            'headers' => array(
                'User-Agent' => 'NXTRUNN Run Club Directory/1.0 (support@nxtrunn.com)'
            ),
            'timeout' => 15
        ));
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! empty( $data ) && isset( $data[0]['lat'] ) ) {
            
            $result = array(
                'lat' => floatval( $data[0]['lat'] ),
                'lng' => floatval( $data[0]['lon'] ),
                'display_name' => $data[0]['display_name']
            );
            
            // Cache for 30 days
            set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );
            
            return $result;
        }
        
        return false;
    }
}