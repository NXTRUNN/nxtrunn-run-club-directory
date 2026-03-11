<?php
/**
 * Handle badge display logic
 */
class NXTRUNN_Badges {
    
    /**
     * Get badges for a club
     */
    public static function get_club_badges( $post_id ) {
        
        $badges = array();
        
        if ( get_post_meta( $post_id, '_nxtrunn_is_woman_run', true ) === '1' ) {
            $badges[] = array(
                'type' => 'woman_run',
                'label' => 'Woman-Run',
                'icon' => '🌸',
                'color' => get_option( 'nxtrunn_badge_color_woman', '#d77aa0' )
            );
        }
        
        if ( get_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', true ) === '1' ) {
            $badges[] = array(
                'type' => 'bipoc_owned',
                'label' => 'BIPOC-Owned',
                'icon' => '✊🏿',
                'color' => get_option( 'nxtrunn_badge_color_bipoc', '#8f657c' )
            );
        }
        
        return apply_filters( 'nxtrunn_club_badges', $badges, $post_id );
    }
    
    /**
     * Display badges HTML
     */
    public static function display_badges( $post_id ) {
        
        $badges = self::get_club_badges( $post_id );
        
        if ( empty( $badges ) ) {
            return '';
        }
        
        $html = '<div class="nxtrunn-badges">';
        
        foreach ( $badges as $badge ) {
            $html .= sprintf(
                '<span class="nxtrunn-badge nxtrunn-badge-%s" data-color="%s">%s %s</span>',
                esc_attr( $badge['type'] ),
                esc_attr( $badge['color'] ),
                $badge['icon'],
                esc_html( $badge['label'] )
            );
        }
        
        $html .= '</div>';
        
        return apply_filters( 'nxtrunn_badge_output', $html, $post_id );
    }
    
    /**
     * Check if club has specific badge
     */
    public static function has_badge( $post_id, $badge_type ) {
        
        switch ( $badge_type ) {
            case 'woman_run':
                return get_post_meta( $post_id, '_nxtrunn_is_woman_run', true ) === '1';
            case 'bipoc_owned':
                return get_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', true ) === '1';
            default:
                return false;
        }
    }
}