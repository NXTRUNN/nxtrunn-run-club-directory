<?php
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
$tabs = array(
    'settings' => 'Settings',
    'outreach' => 'Outreach & Growth',
);
?>
<div class="wrap">
    <h1>NXTRUNN Run Club Directory</h1>

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper">
        <?php foreach ( $tabs as $slug => $label ) : ?>
        <a href="?page=nxtrunn-settings&tab=<?php echo $slug; ?>"
           class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html( $label ); ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <?php if ( $active_tab === 'settings' ) : ?>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'nxtrunn_settings' );
        do_settings_sections( 'nxtrunn_settings' );
        submit_button();
        ?>
    </form>

    <hr>

    <h2>Shortcodes</h2>
    <p>Use these shortcodes to display the directory and submission form:</p>

    <table class="widefat">
        <thead>
            <tr>
                <th>Shortcode</th>
                <th>Description</th>
                <th>Example</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[nxtrunn_runclub_directory]</code></td>
                <td>Display the full directory with filters</td>
                <td><code>[nxtrunn_runclub_directory per_page="12" columns="3"]</code></td>
            </tr>
            <tr>
                <td><code>[nxtrunn_runclub_form]</code></td>
                <td>Display the club submission form</td>
                <td><code>[nxtrunn_runclub_form]</code></td>
            </tr>
        </tbody>
    </table>

    <hr>

    <h2>Pace Data Migration</h2>
    <p>Assign pace ranges (min/max seconds) to clubs based on their existing pace taxonomy terms. Clubs already updated by owners will be skipped.</p>
    <button type="button" class="button button-secondary" id="nxtrunn-migrate-pace">Run Pace Migration</button>
    <span id="nxtrunn-pace-migrate-result" style="margin-left: 12px; font-weight: 600;"></span>
    <script>
    jQuery(function($){
        $('#nxtrunn-migrate-pace').on('click', function(){
            var $btn = $(this), $result = $('#nxtrunn-pace-migrate-result');
            $btn.prop('disabled', true).text('Migrating...');
            $result.text('');
            $.post(ajaxurl, {
                action: 'nxtrunn_migrate_pace',
                nonce: '<?php echo wp_create_nonce("nxtrunn_nonce"); ?>'
            }, function(resp){
                $btn.prop('disabled', false).text('Run Pace Migration');
                if (resp.success) {
                    $result.css('color', '#5E9070').text(
                        resp.data.migrated + ' clubs migrated, ' + resp.data.skipped + ' skipped (owner-set), ' + resp.data.total + ' total'
                    );
                } else {
                    $result.css('color', '#C86848').text('Migration failed.');
                }
            });
        });
    });
    </script>

    <hr>

    <h2>System Information</h2>
    <table class="widefat">
        <tbody>
            <tr>
                <th>Plugin Version:</th>
                <td><?php echo NXTRUNN_VERSION; ?></td>
            </tr>
            <tr>
                <th>WordPress Version:</th>
                <td><?php echo get_bloginfo( 'version' ); ?></td>
            </tr>
            <tr>
                <th>PHP Version:</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th>Total Clubs:</th>
                <td><?php echo wp_count_posts( 'run_club' )->publish; ?> published</td>
            </tr>
            <tr>
                <th>Pending Verification:</th>
                <td><?php echo wp_count_posts( 'run_club' )->pending; ?> pending</td>
            </tr>
        </tbody>
    </table>

    <?php elseif ( $active_tab === 'outreach' ) : ?>

    <?php
    global $wpdb;

    $total = wp_count_posts( 'run_club' )->publish;

    $sent = (int) $wpdb->get_var( "
        SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
        WHERE meta_key = '_nxtrunn_outreach_sent'
    " );

    $claimed = (int) $wpdb->get_var( "
        SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
        WHERE meta_key = '_nxtrunn_claimed' AND meta_value = '1'
    " );

    $organic = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_nxtrunn_claim_source' AND meta_value = 'organic'
    " );

    $outreach_claimed = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_nxtrunn_claim_source' AND meta_value = 'outreach'
    " );

    $initiated = (int) $wpdb->get_var( "
        SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
        WHERE meta_key = '_nxtrunn_claim_initiated'
    " );

    $claim_rate = $sent > 0 ? round( ( $claimed / $sent ) * 100 ) : 0;
    ?>

    <style>
        .nxtrunn-stat-row { display: flex; gap: 16px; flex-wrap: wrap; margin: 24px 0; }
        .nxtrunn-stat { background: #fff; border: 1px solid #E2DAE8; border-radius: 12px; padding: 20px; flex: 1; min-width: 120px; text-align: center; }
        .nxtrunn-stat .num { display: block; font-size: 32px; font-weight: 700; color: #7C5A78; }
        .nxtrunn-stat .label { display: block; font-size: 13px; color: #6D6070; margin-top: 4px; }
        .nxtrunn-source-row { display: flex; gap: 16px; margin-bottom: 24px; }
        .nxtrunn-source-card { background: #fff; border: 1px solid #E2DAE8; border-radius: 12px; padding: 20px; flex: 1; text-align: center; }
        .nxtrunn-source-card .num { display: block; font-size: 28px; font-weight: 700; color: #1C1720; }
        .nxtrunn-source-card .label { display: block; font-size: 14px; font-weight: 600; margin-top: 4px; }
        .nxtrunn-source-card .desc { display: block; font-size: 12px; color: #6D6070; }
        .nxtrunn-filter-bar { display: flex; gap: 8px; margin: 16px 0; flex-wrap: wrap; }
        .nxtrunn-filter-pill-admin { padding: 6px 14px; border-radius: 9999px; border: 1px solid #E2DAE8; background: #fff; color: #6D6070; text-decoration: none; font-size: 13px; }
        .nxtrunn-filter-pill-admin.active { background: #7C5A78; color: #fff; border-color: #7C5A78; }
    </style>

    <div class="nxtrunn-stat-row">
        <div class="nxtrunn-stat"><span class="num"><?php echo $total; ?></span><span class="label">Total Clubs</span></div>
        <div class="nxtrunn-stat"><span class="num"><?php echo $sent; ?></span><span class="label">Outreach Sent</span></div>
        <div class="nxtrunn-stat"><span class="num"><?php echo $initiated; ?></span><span class="label">Claim Initiated</span></div>
        <div class="nxtrunn-stat"><span class="num"><?php echo $claimed; ?></span><span class="label">Claimed</span></div>
        <div class="nxtrunn-stat"><span class="num"><?php echo $claim_rate; ?>%</span><span class="label">Claim Rate</span></div>
    </div>

    <div class="nxtrunn-source-row">
        <div class="nxtrunn-source-card">
            <span class="num"><?php echo $organic; ?></span>
            <span class="label">Organic Claims</span>
            <span class="desc">Found NXTRUNN themselves</span>
        </div>
        <div class="nxtrunn-source-card">
            <span class="num"><?php echo $outreach_claimed; ?></span>
            <span class="label">Outreach Claims</span>
            <span class="desc">Came from your email</span>
        </div>
    </div>

    <?php
    // Club table with filter
    $filter = sanitize_text_field( $_GET['outreach_filter'] ?? 'all' );

    $meta_query = array();
    switch ( $filter ) {
        case 'no_email':
            $meta_query = array(
                'relation' => 'OR',
                array( 'key' => '_nxtrunn_outreach_email', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_nxtrunn_outreach_email', 'value' => '', 'compare' => '=' ),
            );
            break;
        case 'sent':
            $meta_query = array(
                array( 'key' => '_nxtrunn_outreach_sent', 'compare' => 'EXISTS' ),
                array(
                    'relation' => 'OR',
                    array( 'key' => '_nxtrunn_claimed', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_nxtrunn_claimed', 'value' => '1', 'compare' => '!=' ),
                ),
            );
            break;
        case 'claimed':
            $meta_query = array( array( 'key' => '_nxtrunn_claimed', 'value' => '1' ) );
            break;
    }

    $clubs = get_posts( array(
        'post_type'      => 'run_club',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => ! empty( $meta_query ) ? $meta_query : array(),
    ) );

    $filters = array( 'all', 'no_email', 'sent', 'claimed' );
    $labels  = array( 'All', 'No Email', 'Outreach Sent', 'Claimed' );
    ?>

    <div class="nxtrunn-filter-bar">
        <?php foreach ( $filters as $i => $f ) : ?>
        <a href="?page=nxtrunn-settings&tab=outreach&outreach_filter=<?php echo $f; ?>"
           class="nxtrunn-filter-pill-admin <?php echo $filter === $f ? 'active' : ''; ?>">
            <?php echo $labels[ $i ]; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Club</th>
                <th>Location</th>
                <th>Email</th>
                <th>Outreach</th>
                <th>Claimed</th>
                <th>Source</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $clubs ) ) : ?>
            <tr><td colspan="7">No clubs match this filter.</td></tr>
        <?php endif; ?>
        <?php foreach ( $clubs as $club ) :
            $email     = get_post_meta( $club->ID, '_nxtrunn_outreach_email', true );
            $o_sent    = get_post_meta( $club->ID, '_nxtrunn_outreach_sent', true );
            $is_claimed = get_post_meta( $club->ID, '_nxtrunn_claimed', true ) === '1';
            $source    = get_post_meta( $club->ID, '_nxtrunn_claim_source', true );
            $city      = get_post_meta( $club->ID, '_nxtrunn_city', true );
            $state     = get_post_meta( $club->ID, '_nxtrunn_state', true );

            if ( $is_claimed ) { $status = 'Claimed'; $scolor = '#5E9070'; }
            elseif ( $o_sent ) { $status = 'Sent'; $scolor = '#C9903C'; }
            elseif ( $email )  { $status = 'Has Email'; $scolor = '#4E6FA8'; }
            else               { $status = 'No Email'; $scolor = '#B5A8BA'; }
        ?>
        <tr>
            <td><a href="<?php echo get_edit_post_link( $club->ID ); ?>"><?php echo esc_html( $club->post_title ); ?></a></td>
            <td><?php echo esc_html( trim( "$city, $state", ', ' ) ); ?></td>
            <td><?php echo $email ? esc_html( $email ) : '&mdash;'; ?></td>
            <td><?php echo $o_sent ? date( 'M j', $o_sent ) : '&mdash;'; ?></td>
            <td><?php echo $is_claimed ? 'Yes' : '&mdash;'; ?></td>
            <td><?php echo $source ? ucfirst( $source ) : '&mdash;'; ?></td>
            <td><span style="color:<?php echo $scolor; ?>; font-weight:600;"><?php echo $status; ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>
