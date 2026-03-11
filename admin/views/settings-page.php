<div class="wrap">
    <h1>NXTRUNN Run Club Directory Settings</h1>
    
    <?php settings_errors(); ?>
    
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
</div>