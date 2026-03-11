<?php
/**
 * Distance calculations using Haversine formula
 */
class NXTRUNN_Distance {
    
    /**
     * Calculate distance between two coordinates
     * 
     * @param float $lat1 Latitude of point 1
     * @param float $lng1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lng2 Longitude of point 2
     * @param string $unit 'mi' for miles, 'km' for kilometers
     * @return float Distance
     */
    public static function calculate( $lat1, $lng1, $lat2, $lng2, $unit = 'mi' ) {
        
        $earth_radius = ( $unit === 'km' ) ? 6371 : 3959;
        
        $dLat = deg2rad( $lat2 - $lat1 );
        $dLng = deg2rad( $lng2 - $lng1 );
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2( sqrt($a), sqrt(1-$a) );
        $distance = $earth_radius * $c;
        
        return $distance;
    }
    
    /**
     * Get distance unit based on country
     * 
     * @param string $country_code ISO country code
     * @return string 'mi' or 'km'
     */
    public static function get_unit_by_country( $country_code ) {
        
        // Countries that use miles
        $miles_countries = array( 'US', 'GB', 'LR', 'MM' );
        
        return in_array( $country_code, $miles_countries ) ? 'mi' : 'km';
    }
    
    /**
     * Format distance with unit
     * 
     * @param float $distance
     * @param string $unit
     * @return string
     */
    public static function format( $distance, $unit = 'mi' ) {
        
        $rounded = round( $distance, 1 );
        $unit_label = ( $unit === 'km' ) ? 'km' : 'mi';
        
        return $rounded . ' ' . $unit_label;
    }
}