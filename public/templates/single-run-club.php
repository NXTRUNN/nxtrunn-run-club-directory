<?php
/**
 * Single Run Club Template
 */

get_header();

while ( have_posts() ) : the_post();
    
    $club_id = get_the_ID();
    $city = get_post_meta( $club_id, '_nxtrunn_city', true );
    $state = get_post_meta( $club_id, '_nxtrunn_state', true );
    $country = get_post_meta( $club_id, '_nxtrunn_country', true );
    $website = get_post_meta( $club_id, '_nxtrunn_website', true );
    $instagram = get_post_meta( $club_id, '_nxtrunn_instagram', true );
    $meeting_location = get_post_meta( $club_id, '_nxtrunn_meeting_location', true );
    $sponsor = get_post_meta( $club_id, '_nxtrunn_sponsor', true );
    $description = get_post_field( 'post_content', $club_id );
    
    // Get directory page URL
    $directory_page_id = get_option( 'nxtrunn_directory_page_id' );
    if ( $directory_page_id ) {
        $directory_url = get_permalink( $directory_page_id );
    } else {
        $pages = get_posts( array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            's' => '[nxtrunn_runclub_directory]'
        ));
        if ( ! empty( $pages ) ) {
            $directory_url = get_permalink( $pages[0]->ID );
        } else {
            $directory_url = home_url( '/' );
        }
    }
    
    // Sponsor logo mapping
    $sponsor_data = array(
        'adidas' => array(
            'name' => 'adidas',
            'logo' => 'https://logo.clearbit.com/adidas.com'
        ),
        'altra' => array(
            'name' => 'Altra',
            'logo' => 'https://logo.clearbit.com/altrarunning.com'
        ),
        'asics' => array(
            'name' => 'ASICS',
            'logo' => 'https://logo.clearbit.com/asics.com'
        ),
        'bandit' => array(
            'name' => 'Bandit Running',
            'logo' => ''
        ),
        'brooks' => array(
            'name' => 'Brooks',
            'logo' => 'https://logo.clearbit.com/brooksrunning.com'
        ),
        'ciele' => array(
            'name' => 'Ciele Athletics',
            'logo' => ''
        ),
        'craft' => array(
            'name' => 'Craft',
            'logo' => ''
        ),
        'hoka' => array(
            'name' => 'HOKA',
            'logo' => 'https://logo.clearbit.com/hoka.com'
        ),
        'janji' => array(
            'name' => 'Janji',
            'logo' => ''
        ),
        'karhu' => array(
            'name' => 'Karhu',
            'logo' => ''
        ),
        'lululemon' => array(
            'name' => 'lululemon',
            'logo' => 'https://logo.clearbit.com/lululemon.com'
        ),
        'mizuno' => array(
            'name' => 'Mizuno',
            'logo' => 'https://logo.clearbit.com/mizunousa.com'
        ),
        'new-balance' => array(
            'name' => 'New Balance',
            'logo' => 'https://logo.clearbit.com/newbalance.com'
        ),
        'newton' => array(
            'name' => 'Newton Running',
            'logo' => ''
        ),
        'nike' => array(
            'name' => 'Nike',
            'logo' => 'https://logo.clearbit.com/nike.com'
        ),
        'on-running' => array(
            'name' => 'On Running',
            'logo' => 'https://logo.clearbit.com/on-running.com'
        ),
        'puma' => array(
            'name' => 'PUMA',
            'logo' => 'https://logo.clearbit.com/puma.com'
        ),
        'rabbit' => array(
            'name' => 'Rabbit',
            'logo' => ''
        ),
        'reebok' => array(
            'name' => 'Reebok',
            'logo' => 'https://logo.clearbit.com/reebok.com'
        ),
        'salomon' => array(
            'name' => 'Salomon',
            'logo' => 'https://logo.clearbit.com/salomon.com'
        ),
        'saucony' => array(
            'name' => 'Saucony',
            'logo' => 'https://logo.clearbit.com/saucony.com'
        ),
        'saysky' => array(
            'name' => 'Saysky',
            'logo' => ''
        ),
        'topo' => array(
            'name' => 'Topo Athletic',
            'logo' => ''
        ),
        'tracksmith' => array(
            'name' => 'Tracksmith',
            'logo' => ''
        ),
        'under-armour' => array(
            'name' => 'Under Armour',
            'logo' => 'https://logo.clearbit.com/underarmour.com'
        ),
    );
    
    ?>
    
    <style>
    .nxtrunn-back-link {
        padding-top: 20px;
        margin-bottom: 30px;
    }
    @media (max-width: 767px) {
        .nxtrunn-back-link {
            padding-top: 80px;
        }
    }
    </style>
    
    <div class="nxtrunn-single-club-wrapper" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <div class="nxtrunn-back-link">
            <a href="<?php echo esc_url( $directory_url ); ?>" style="color: var(--color-primary, #7C5A78); text-decoration: none; font-weight: 600;">
                ← Back to Directory
            </a>
        </div>
        
        <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            
            <div style="display: flex; gap: 40px; margin-bottom: 30px; flex-wrap: wrap;">
                
                <?php if ( has_post_thumbnail() ) : ?>
                <div style="flex: 0 0 250px;">
                    <?php the_post_thumbnail( 'large', array( 'style' => 'width: 100%; height: auto; border-radius: 8px;' ) ); ?>
                </div>
                <?php endif; ?>
                
                <div style="flex: 1; min-width: 300px;">
                    
                    <?php echo NXTRUNN_Badges::display_badges( $club_id ); ?>
                    
                    <h1 style="margin: 10px 0 20px 0; font-size: 32px;"><?php the_title(); ?></h1>
                    
                    <p style="font-size: 18px; color: #666; margin-bottom: 20px;">
                        📍 <?php echo esc_html( $city . ', ' . $state . ', ' . $country ); ?>
                    </p>
                    
                    <?php if ( $description ) : ?>
                    <div style="margin-bottom: 20px; line-height: 1.6;">
                        <?php echo wpautop( wp_kses_post( $description ) ); ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 30px 0;">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                
                <div>
                    <h3 style="margin: 0 0 15px 0; color: var(--color-primary, #7C5A78);">Club Details</h3>
                    
                    <?php
                    $pace_terms = wp_get_post_terms( $club_id, 'run_pace' );
                    if ( ! is_wp_error( $pace_terms ) && ! empty( $pace_terms ) ) :
                    ?>
                    <p style="margin-bottom: 15px;"><strong>🏃🏾 Pace:</strong><br>
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $pace_terms, 'name' ) ) ); ?></p>
                    <?php endif; ?>
                    
                    <?php
                    $vibe_terms = wp_get_post_terms( $club_id, 'run_vibe' );
                    if ( ! is_wp_error( $vibe_terms ) && ! empty( $vibe_terms ) ) :
                    ?>
                    <p style="margin-bottom: 15px;"><strong>✨ Vibe:</strong><br>
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $vibe_terms, 'name' ) ) ); ?></p>
                    <?php endif; ?>
                    
                    <?php
                    $days_terms = wp_get_post_terms( $club_id, 'run_days' );
                    if ( ! is_wp_error( $days_terms ) && ! empty( $days_terms ) ) :
                    ?>
                    <p style="margin-bottom: 15px;"><strong>📅 Days:</strong><br>
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $days_terms, 'name' ) ) ); ?></p>
                    <?php endif; ?>
                    
                    <?php if ( $meeting_location ) : ?>
                    <p style="margin-bottom: 15px;"><strong>📍 Meeting Location:</strong><br>
                    <?php echo esc_html( $meeting_location ); ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 style="margin: 0 0 15px 0; color: var(--color-primary, #7C5A78);">Connect</h3>
                    
                    <?php if ( $website ) : ?>
                    <p style="margin-bottom: 10px;">
                        <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; background: var(--color-primary, #7C5A78); color: white; text-decoration: none; border-radius: 6px;">
                            🌐 Visit Website
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ( $instagram ) : ?>
                    <p style="margin-bottom: 10px;">
                        <a href="https://instagram.com/<?php echo esc_attr( ltrim( $instagram, '@' ) ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; background: var(--color-primary, #7C5A78); color: white; text-decoration: none; border-radius: 6px;">
                            📸 Follow on Instagram
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ( $sponsor ) : ?>
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <p style="font-size: 13px; color: #999; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Sponsored By</p>
                        
                        <?php
                        $sponsor_info = isset( $sponsor_data[$sponsor] ) ? $sponsor_data[$sponsor] : array(
                            'name' => $sponsor,
                            'logo' => ''
                        );
                        ?>
                        
                        <?php if ( ! empty( $sponsor_info['logo'] ) ) : ?>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
                            <img src="<?php echo esc_url( $sponsor_info['logo'] ); ?>" alt="<?php echo esc_attr( $sponsor_info['name'] ); ?>" style="height: 40px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
                            <span style="font-size: 16px; font-weight: 600; color: #333;"><?php echo esc_html( $sponsor_info['name'] ); ?></span>
                        </div>
                        <?php else : ?>
                        <div style="padding: 15px; background: #f8f8f8; border-radius: 8px;">
                            <span style="font-size: 16px; font-weight: 600; color: #333;">🏃 <?php echo esc_html( $sponsor_info['name'] ); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </div>
        
    </div>
    
    <?php
endwhile;

get_footer();