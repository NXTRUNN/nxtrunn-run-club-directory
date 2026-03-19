<?php
/**
 * Import clubs from Daily.nyc JSON data + CSV outreach list
 *
 * Adds an admin page under Run Clubs > Import Clubs.
 * Supports: JSON import, CSV import, and bulk geocoding.
 */
class NXTRUNN_Import {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_import_page' ) );
        add_action( 'wp_ajax_nxtrunn_run_import', array( $this, 'ajax_import' ) );
        add_action( 'wp_ajax_nxtrunn_import_single', array( $this, 'ajax_import_single' ) );
        add_action( 'wp_ajax_nxtrunn_csv_import', array( $this, 'ajax_csv_import' ) );
        add_action( 'wp_ajax_nxtrunn_csv_import_single', array( $this, 'ajax_csv_import_single' ) );
        add_action( 'wp_ajax_nxtrunn_geocode_scan', array( $this, 'ajax_geocode_scan' ) );
        add_action( 'wp_ajax_nxtrunn_geocode_single', array( $this, 'ajax_geocode_single' ) );
    }

    public function add_import_page() {
        add_submenu_page(
            'edit.php?post_type=run_club',
            'Import Clubs',
            'Import Clubs',
            'manage_options',
            'nxtrunn-import',
            array( $this, 'render_import_page' )
        );
    }

    /**
     * Render the import admin page
     */
    public function render_import_page() {
        $nonce = wp_create_nonce( 'nxtrunn_import_nonce' );
        ?>
        <div class="wrap">
            <h1>Run Club Import &amp; Tools</h1>

            <!-- ===================== SECTION 1: Daily.nyc JSON Import ===================== -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; max-width: 700px;">
                <h2 style="margin-top: 0;">1. Daily.nyc JSON Import</h2>
                <p>Import clubs from <code>daily-nyc-all-clubs.json</code> in the plugin folder.</p>

                <p><strong>Status:</strong> <span id="nxtrunn-import-status">Ready</span></p>
                <div id="nxtrunn-import-progress" style="display:none; margin: 16px 0;">
                    <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden; height: 24px;">
                        <div id="nxtrunn-import-bar" style="background: #7C5A78; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">0%</div>
                    </div>
                </div>
                <div id="nxtrunn-import-log" style="display:none; max-height: 300px; overflow-y: auto; background: #1C1720; color: #B5A8BA; font-family: monospace; font-size: 12px; padding: 12px; border-radius: 4px; margin: 16px 0;"></div>

                <p><label><input type="checkbox" id="nxtrunn-import-draft" value="1"> Import as Draft</label></p>
                <p><label><input type="checkbox" id="nxtrunn-import-images" value="1" checked> Download avatar images</label></p>

                <button id="nxtrunn-start-import" class="button button-primary" style="margin-top: 8px;">Start JSON Import</button>
                <button id="nxtrunn-stop-import" class="button button-secondary" style="margin-top: 8px; display: none;">Stop</button>
            </div>

            <!-- ===================== SECTION 2: CSV Import ===================== -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; max-width: 700px;">
                <h2 style="margin-top: 0;">2. CSV Outreach List Import</h2>
                <p>Import BIPOC run clubs from the outreach CSV. Maps: Club Name, City, State, Instagram Handle. All clubs auto-tagged BIPOC-Owned.</p>

                <p><strong>Status:</strong> <span id="nxtrunn-csv-status">Ready</span></p>
                <div id="nxtrunn-csv-progress" style="display:none; margin: 16px 0;">
                    <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden; height: 24px;">
                        <div id="nxtrunn-csv-bar" style="background: #8B6340; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">0%</div>
                    </div>
                </div>
                <div id="nxtrunn-csv-log" style="display:none; max-height: 300px; overflow-y: auto; background: #1C1720; color: #B5A8BA; font-family: monospace; font-size: 12px; padding: 12px; border-radius: 4px; margin: 16px 0;"></div>

                <p><label><input type="checkbox" id="nxtrunn-csv-draft" value="1"> Import as Draft</label></p>

                <button id="nxtrunn-start-csv" class="button button-primary" style="margin-top: 8px;">Start CSV Import</button>
                <button id="nxtrunn-stop-csv" class="button button-secondary" style="margin-top: 8px; display: none;">Stop</button>
            </div>

            <!-- ===================== SECTION 3: Bulk Geocoder ===================== -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; max-width: 700px;">
                <h2 style="margin-top: 0;">3. Bulk Geocode Clubs</h2>
                <p>Adds latitude/longitude to all clubs that are missing coordinates. Uses city + state + country to look up GPS via OpenStreetMap (Nominatim). Required for "Near Me" search to work.</p>
                <p><em>Nominatim rate limit: 1 request/second — this will take ~3 minutes for 174 clubs.</em></p>

                <p><strong>Status:</strong> <span id="nxtrunn-geo-status">Ready</span></p>
                <div id="nxtrunn-geo-progress" style="display:none; margin: 16px 0;">
                    <div style="background: #f0f0f0; border-radius: 4px; overflow: hidden; height: 24px;">
                        <div id="nxtrunn-geo-bar" style="background: #5E9070; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">0%</div>
                    </div>
                </div>
                <div id="nxtrunn-geo-log" style="display:none; max-height: 300px; overflow-y: auto; background: #1C1720; color: #B5A8BA; font-family: monospace; font-size: 12px; padding: 12px; border-radius: 4px; margin: 16px 0;"></div>

                <button id="nxtrunn-start-geocode" class="button button-primary" style="margin-top: 8px;">Scan &amp; Geocode</button>
                <button id="nxtrunn-stop-geocode" class="button button-secondary" style="margin-top: 8px; display: none;">Stop</button>
            </div>
        </div>

        <script>
        (function($) {
            var nonce = '<?php echo $nonce; ?>';

            /* ========== SHARED HELPERS ========== */
            function log(selector, msg) {
                var $log = $(selector);
                $log.append(msg + '\n');
                $log.scrollTop($log[0].scrollHeight);
            }

            /* ========== SECTION 1: JSON IMPORT ========== */
            (function() {
                var running = false, stopped = false, clubs = [], idx = 0, ok = 0, skip = 0, fail = 0;

                $('#nxtrunn-start-import').on('click', function() {
                    if (running) return;
                    running = true; stopped = false; idx = 0; ok = 0; skip = 0; fail = 0;
                    $(this).prop('disabled', true).text('Loading...');
                    $('#nxtrunn-stop-import').show();
                    $('#nxtrunn-import-progress').show();
                    $('#nxtrunn-import-log').show().empty();

                    $.post(ajaxurl, { action: 'nxtrunn_run_import', nonce: nonce, step: 'load' }, function(r) {
                        if (r.success) {
                            clubs = r.data.clubs;
                            log('#nxtrunn-import-log', 'Loaded ' + clubs.length + ' clubs from JSON');
                            log('#nxtrunn-import-log', '---');
                            $('#nxtrunn-start-import').text('Importing...');
                            next();
                        } else {
                            log('#nxtrunn-import-log', 'ERROR: ' + r.data.message);
                            reset();
                        }
                    }).fail(function() { log('#nxtrunn-import-log', 'ERROR: AJAX failed'); reset(); });
                });

                $('#nxtrunn-stop-import').on('click', function() { stopped = true; $(this).text('Stopping...'); });

                function next() {
                    if (stopped || idx >= clubs.length) {
                        log('#nxtrunn-import-log', '---');
                        log('#nxtrunn-import-log', 'DONE: ' + ok + ' imported, ' + skip + ' skipped, ' + fail + ' failed');
                        $('#nxtrunn-import-status').text('Complete — ' + ok + ' imported, ' + skip + ' skipped, ' + fail + ' failed');
                        reset(); return;
                    }
                    var club = clubs[idx];
                    $.post(ajaxurl, {
                        action: 'nxtrunn_import_single', nonce: nonce,
                        club: JSON.stringify(club),
                        as_draft: $('#nxtrunn-import-draft').is(':checked') ? 1 : 0,
                        download_images: $('#nxtrunn-import-images').is(':checked') ? 1 : 0
                    }, function(r) {
                        idx++;
                        var pct = Math.round((idx / clubs.length) * 100);
                        $('#nxtrunn-import-bar').css('width', pct + '%').text(pct + '%');
                        $('#nxtrunn-import-status').text('Importing ' + idx + ' / ' + clubs.length);
                        if (r.success) { if (r.data.skipped) { skip++; log('#nxtrunn-import-log', 'SKIP: ' + club.name); } else { ok++; log('#nxtrunn-import-log', 'OK: ' + club.name + (r.data.image ? ' [+img]' : '')); } }
                        else { fail++; log('#nxtrunn-import-log', 'FAIL: ' + club.name + ' — ' + (r.data ? r.data.message : 'Unknown')); }
                        setTimeout(next, 200);
                    }).fail(function() { idx++; fail++; log('#nxtrunn-import-log', 'FAIL: ' + club.name + ' — AJAX error'); setTimeout(next, 200); });
                }

                function reset() {
                    running = false; stopped = false;
                    $('#nxtrunn-start-import').prop('disabled', false).text('Start JSON Import');
                    $('#nxtrunn-stop-import').hide().text('Stop');
                }
            })();

            /* ========== SECTION 2: CSV IMPORT ========== */
            (function() {
                var running = false, stopped = false, clubs = [], idx = 0, ok = 0, skip = 0, fail = 0;

                $('#nxtrunn-start-csv').on('click', function() {
                    if (running) return;
                    running = true; stopped = false; idx = 0; ok = 0; skip = 0; fail = 0;
                    $(this).prop('disabled', true).text('Loading CSV...');
                    $('#nxtrunn-stop-csv').show();
                    $('#nxtrunn-csv-progress').show();
                    $('#nxtrunn-csv-log').show().empty();

                    $.post(ajaxurl, { action: 'nxtrunn_csv_import', nonce: nonce }, function(r) {
                        if (r.success) {
                            clubs = r.data.clubs;
                            log('#nxtrunn-csv-log', 'Loaded ' + clubs.length + ' clubs from CSV');
                            log('#nxtrunn-csv-log', '---');
                            $('#nxtrunn-start-csv').text('Importing...');
                            next();
                        } else {
                            log('#nxtrunn-csv-log', 'ERROR: ' + r.data.message);
                            reset();
                        }
                    }).fail(function() { log('#nxtrunn-csv-log', 'ERROR: AJAX failed'); reset(); });
                });

                $('#nxtrunn-stop-csv').on('click', function() { stopped = true; $(this).text('Stopping...'); });

                function next() {
                    if (stopped || idx >= clubs.length) {
                        log('#nxtrunn-csv-log', '---');
                        log('#nxtrunn-csv-log', 'DONE: ' + ok + ' imported, ' + skip + ' skipped, ' + fail + ' failed');
                        $('#nxtrunn-csv-status').text('Complete — ' + ok + ' imported, ' + skip + ' skipped, ' + fail + ' failed');
                        reset(); return;
                    }
                    var club = clubs[idx];
                    $.post(ajaxurl, {
                        action: 'nxtrunn_csv_import_single', nonce: nonce,
                        club: JSON.stringify(club),
                        as_draft: $('#nxtrunn-csv-draft').is(':checked') ? 1 : 0
                    }, function(r) {
                        idx++;
                        var pct = Math.round((idx / clubs.length) * 100);
                        $('#nxtrunn-csv-bar').css('width', pct + '%').text(pct + '%');
                        $('#nxtrunn-csv-status').text('Importing ' + idx + ' / ' + clubs.length);
                        if (r.success) { if (r.data.skipped) { skip++; log('#nxtrunn-csv-log', 'SKIP: ' + club.name); } else { ok++; log('#nxtrunn-csv-log', 'OK: ' + club.name + ' (' + club.city + ', ' + club.state + ')'); } }
                        else { fail++; log('#nxtrunn-csv-log', 'FAIL: ' + club.name + ' — ' + (r.data ? r.data.message : 'Unknown')); }
                        setTimeout(next, 200);
                    }).fail(function() { idx++; fail++; log('#nxtrunn-csv-log', 'FAIL: ' + club.name + ' — AJAX error'); setTimeout(next, 200); });
                }

                function reset() {
                    running = false; stopped = false;
                    $('#nxtrunn-start-csv').prop('disabled', false).text('Start CSV Import');
                    $('#nxtrunn-stop-csv').hide().text('Stop');
                }
            })();

            /* ========== SECTION 3: BULK GEOCODER ========== */
            (function() {
                var running = false, stopped = false, clubIds = [], idx = 0, ok = 0, skip = 0, fail = 0;

                $('#nxtrunn-start-geocode').on('click', function() {
                    if (running) return;
                    running = true; stopped = false; idx = 0; ok = 0; skip = 0; fail = 0;
                    $(this).prop('disabled', true).text('Scanning...');
                    $('#nxtrunn-stop-geocode').show();
                    $('#nxtrunn-geo-progress').show();
                    $('#nxtrunn-geo-log').show().empty();

                    $.post(ajaxurl, { action: 'nxtrunn_geocode_scan', nonce: nonce }, function(r) {
                        if (r.success) {
                            clubIds = r.data.clubs;
                            if (clubIds.length === 0) {
                                log('#nxtrunn-geo-log', 'All clubs already have coordinates!');
                                $('#nxtrunn-geo-status').text('Nothing to geocode');
                                reset(); return;
                            }
                            log('#nxtrunn-geo-log', 'Found ' + clubIds.length + ' clubs missing coordinates');
                            log('#nxtrunn-geo-log', '(~' + Math.ceil(clubIds.length * 1.2 / 60) + ' min at 1 req/sec)');
                            log('#nxtrunn-geo-log', '---');
                            $('#nxtrunn-start-geocode').text('Geocoding...');
                            next();
                        } else {
                            log('#nxtrunn-geo-log', 'ERROR: ' + r.data.message);
                            reset();
                        }
                    }).fail(function() { log('#nxtrunn-geo-log', 'ERROR: AJAX failed'); reset(); });
                });

                $('#nxtrunn-stop-geocode').on('click', function() { stopped = true; $(this).text('Stopping...'); });

                function next() {
                    if (stopped || idx >= clubIds.length) {
                        log('#nxtrunn-geo-log', '---');
                        log('#nxtrunn-geo-log', 'DONE: ' + ok + ' geocoded, ' + skip + ' no location data, ' + fail + ' failed');
                        $('#nxtrunn-geo-status').text('Complete — ' + ok + ' geocoded, ' + skip + ' skipped, ' + fail + ' failed');
                        reset(); return;
                    }
                    var club = clubIds[idx];
                    $.post(ajaxurl, {
                        action: 'nxtrunn_geocode_single', nonce: nonce,
                        post_id: club.id
                    }, function(r) {
                        idx++;
                        var pct = Math.round((idx / clubIds.length) * 100);
                        $('#nxtrunn-geo-bar').css('width', pct + '%').text(pct + '%');
                        $('#nxtrunn-geo-status').text('Geocoding ' + idx + ' / ' + clubIds.length);
                        if (r.success) {
                            if (r.data.skipped) { skip++; log('#nxtrunn-geo-log', 'SKIP: ' + club.title + ' (no city/state)'); }
                            else { ok++; log('#nxtrunn-geo-log', 'OK: ' + club.title + ' → ' + r.data.lat.toFixed(4) + ', ' + r.data.lng.toFixed(4)); }
                        } else { fail++; log('#nxtrunn-geo-log', 'FAIL: ' + club.title + ' — ' + (r.data ? r.data.message : 'Unknown')); }
                        // 1.2 second delay to respect Nominatim rate limit
                        setTimeout(next, 1200);
                    }).fail(function() { idx++; fail++; log('#nxtrunn-geo-log', 'FAIL: ' + club.title + ' — AJAX error'); setTimeout(next, 1200); });
                }

                function reset() {
                    running = false; stopped = false;
                    $('#nxtrunn-start-geocode').prop('disabled', false).text('Scan & Geocode');
                    $('#nxtrunn-stop-geocode').hide().text('Stop');
                }
            })();

        })(jQuery);
        </script>
        <?php
    }

    /**
     * AJAX: Load JSON file and return club list
     */
    public function ajax_import() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $json_file = NXTRUNN_PLUGIN_DIR . 'daily-nyc-all-clubs.json';

        if ( ! file_exists( $json_file ) ) {
            wp_send_json_error( array( 'message' => 'JSON file not found. Place daily-nyc-all-clubs.json in the plugin folder.' ) );
        }

        $json = file_get_contents( $json_file );
        $clubs = json_decode( $json, true );

        if ( ! is_array( $clubs ) ) {
            wp_send_json_error( array( 'message' => 'Invalid JSON format' ) );
        }

        wp_send_json_success( array( 'clubs' => $clubs, 'count' => count( $clubs ) ) );
    }

    /**
     * AJAX: Import a single club
     */
    public function ajax_import_single() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $club_json = isset( $_POST['club'] ) ? stripslashes( $_POST['club'] ) : '';
        $club = json_decode( $club_json, true );

        if ( ! $club || empty( $club['name'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid club data' ) );
        }

        $as_draft = isset( $_POST['as_draft'] ) && $_POST['as_draft'] == '1';
        $download_images = isset( $_POST['download_images'] ) && $_POST['download_images'] == '1';

        // Check if club already exists by title
        $existing = new WP_Query( array(
            'post_type'      => 'run_club',
            'title'          => $club['name'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( $existing->have_posts() ) {
            wp_send_json_success( array( 'skipped' => true, 'message' => 'Already exists' ) );
        }

        // Create the post
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $club['name'] ),
            'post_content' => sanitize_textarea_field( $club['description'] ?? '' ),
            'post_type'    => 'run_club',
            'post_status'  => $as_draft ? 'draft' : 'publish',
            'post_author'  => get_current_user_id(),
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }

        // --- Location ---
        $neighborhoods = $club['neighborhoods'] ?? array();
        $city = ! empty( $club['neighborhood_label'] ) ? $club['neighborhood_label'] : '';
        if ( empty( $city ) && ! empty( $neighborhoods ) ) {
            $city = $neighborhoods[0]['name'];
        }

        update_post_meta( $post_id, '_nxtrunn_city', sanitize_text_field( $city ) );
        update_post_meta( $post_id, '_nxtrunn_state', 'NY' );
        update_post_meta( $post_id, '_nxtrunn_country', 'US' );

        // --- Contact / Socials ---
        if ( ! empty( $club['website'] ) ) {
            update_post_meta( $post_id, '_nxtrunn_website', esc_url_raw( $club['website'] ) );
        }

        if ( ! empty( $club['instagram_handle'] ) ) {
            update_post_meta( $post_id, '_nxtrunn_instagram', '@' . sanitize_text_field( ltrim( $club['instagram_handle'], '@' ) ) );
        }

        if ( ! empty( $club['tiktok_handle'] ) ) {
            update_post_meta( $post_id, '_nxtrunn_tiktok', '@' . sanitize_text_field( ltrim( $club['tiktok_handle'], '@' ) ) );
        }

        if ( ! empty( $club['strava_club_id'] ) ) {
            update_post_meta( $post_id, '_nxtrunn_strava', esc_url_raw( 'https://www.strava.com/clubs/' . $club['strava_club_id'] ) );
        }

        // --- Tags → Badges + Taxonomy ---
        $tags = $club['tags'] ?? array();
        $tag_names = array_map( function( $t ) { return $t['name']; }, $tags );

        // Woman-Run badge
        $is_woman = in_array( 'Women', $tag_names ) ? '1' : '0';
        update_post_meta( $post_id, '_nxtrunn_is_woman_run', $is_woman );

        // BIPOC-Owned badge (Black or Latinx)
        $is_bipoc = ( in_array( 'Black', $tag_names ) || in_array( 'Latinx', $tag_names ) ) ? '1' : '0';
        update_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', $is_bipoc );

        // Verification flag if badges claimed
        if ( $is_woman === '1' || $is_bipoc === '1' ) {
            update_post_meta( $post_id, '_nxtrunn_needs_verification', '1' );
        }

        // Vibe taxonomy mapping
        $vibe_map = array(
            'Beginner Friendly' => 'Beginner-Friendly',
            'Competitive'       => 'Competitive',
            'Trail'             => 'Trail Running',
        );

        $vibes = array();
        foreach ( $tag_names as $tag ) {
            if ( isset( $vibe_map[ $tag ] ) ) {
                $vibes[] = $vibe_map[ $tag ];
            }
        }

        if ( ! empty( $vibes ) ) {
            wp_set_object_terms( $post_id, $vibes, 'run_vibe' );
        }

        // --- Source tracking ---
        update_post_meta( $post_id, '_nxtrunn_import_source', 'daily-nyc' );
        update_post_meta( $post_id, '_nxtrunn_import_id', sanitize_text_field( $club['api_id'] ?? $club['slug'] ?? '' ) );

        // --- Avatar image ---
        $image_downloaded = false;
        if ( $download_images && ! empty( $club['avatar_url'] ) ) {
            $image_downloaded = $this->download_and_attach_image( $post_id, $club['avatar_url'], $club['name'] );
        }

        wp_send_json_success( array(
            'post_id' => $post_id,
            'skipped' => false,
            'image'   => $image_downloaded,
        ) );
    }

    /**
     * Download a remote image and set it as the post's featured image
     */
    private function download_and_attach_image( $post_id, $image_url, $club_name ) {

        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Generate a clean filename from club name
        $slug = sanitize_title( $club_name );
        $ext = pathinfo( wp_parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
        if ( empty( $ext ) || ! in_array( strtolower( $ext ), array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) ) ) {
            $ext = 'jpg';
        }

        // Download to temp file
        $tmp = download_url( $image_url, 15 );
        if ( is_wp_error( $tmp ) ) {
            return false;
        }

        $file_array = array(
            'name'     => $slug . '.' . $ext,
            'tmp_name' => $tmp,
        );

        // Sideload into media library
        $attachment_id = media_handle_sideload( $file_array, $post_id, $club_name . ' logo' );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return false;
        }

        // Set as featured image
        set_post_thumbnail( $post_id, $attachment_id );

        return true;
    }

    /* ================================================================
       CSV IMPORT — Outreach list (BIPOC clubs nationwide)
       ================================================================ */

    /**
     * AJAX: Load CSV file and return club list
     */
    public function ajax_csv_import() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $csv_file = NXTRUNN_PLUGIN_DIR . 'NXTRUNN_Outreach_MASTER_V4_FULL_LIST.csv';

        if ( ! file_exists( $csv_file ) ) {
            wp_send_json_error( array( 'message' => 'CSV file not found. Place NXTRUNN_Outreach_MASTER_V4_FULL_LIST.csv in the plugin folder.' ) );
        }

        $clubs = array();
        $handle = fopen( $csv_file, 'r' );

        if ( ! $handle ) {
            wp_send_json_error( array( 'message' => 'Could not open CSV file' ) );
        }

        // Read header row
        $header = fgetcsv( $handle );

        // Find column indices
        $col_map = array_flip( $header );

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $name = isset( $col_map['Club Name'] ) ? trim( $row[ $col_map['Club Name'] ] ) : '';
            if ( empty( $name ) ) continue;

            $city = isset( $col_map['City'] ) ? trim( $row[ $col_map['City'] ] ) : '';
            $state = isset( $col_map['State'] ) ? trim( $row[ $col_map['State'] ] ) : '';
            $ig = isset( $col_map['Instagram Handle'] ) ? trim( $row[ $col_map['Instagram Handle'] ] ) : '';

            // Normalize state — CSV has full uppercase like "CALIFORNIA"
            // Convert to 2-letter code for consistency
            $state = $this->state_to_abbrev( $state );

            $clubs[] = array(
                'name'      => $name,
                'city'      => $city,
                'state'     => $state,
                'instagram' => $ig,
            );
        }

        fclose( $handle );

        wp_send_json_success( array( 'clubs' => $clubs, 'count' => count( $clubs ) ) );
    }

    /**
     * AJAX: Import a single club from CSV data
     */
    public function ajax_csv_import_single() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $club_json = isset( $_POST['club'] ) ? stripslashes( $_POST['club'] ) : '';
        $club = json_decode( $club_json, true );

        if ( ! $club || empty( $club['name'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid club data' ) );
        }

        $as_draft = isset( $_POST['as_draft'] ) && $_POST['as_draft'] == '1';

        // Check if club already exists by title
        $existing = new WP_Query( array(
            'post_type'      => 'run_club',
            'title'          => $club['name'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( $existing->have_posts() ) {
            wp_send_json_success( array( 'skipped' => true, 'message' => 'Already exists' ) );
        }

        // Create the post
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $club['name'] ),
            'post_content' => '',
            'post_type'    => 'run_club',
            'post_status'  => $as_draft ? 'draft' : 'publish',
            'post_author'  => get_current_user_id(),
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }

        // Location
        update_post_meta( $post_id, '_nxtrunn_city', sanitize_text_field( $club['city'] ) );
        update_post_meta( $post_id, '_nxtrunn_state', sanitize_text_field( $club['state'] ) );
        update_post_meta( $post_id, '_nxtrunn_country', 'US' );

        // Instagram
        if ( ! empty( $club['instagram'] ) ) {
            $ig = ltrim( $club['instagram'], '@' );
            update_post_meta( $post_id, '_nxtrunn_instagram', '@' . sanitize_text_field( $ig ) );
        }

        // All clubs in this list are BIPOC-owned
        update_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', '1' );
        update_post_meta( $post_id, '_nxtrunn_is_woman_run', '0' );

        // Source tracking
        update_post_meta( $post_id, '_nxtrunn_import_source', 'outreach-csv' );

        // Mark as unclaimed (for future claim feature)
        update_post_meta( $post_id, '_nxtrunn_claimed', '0' );

        wp_send_json_success( array(
            'post_id' => $post_id,
            'skipped' => false,
        ) );
    }

    /**
     * Convert full state name to 2-letter abbreviation
     */
    private function state_to_abbrev( $state ) {
        $map = array(
            'ALABAMA' => 'AL', 'ALASKA' => 'AK', 'ARIZONA' => 'AZ', 'ARKANSAS' => 'AR',
            'CALIFORNIA' => 'CA', 'COLORADO' => 'CO', 'CONNECTICUT' => 'CT', 'DELAWARE' => 'DE',
            'FLORIDA' => 'FL', 'GEORGIA' => 'GA', 'HAWAII' => 'HI', 'IDAHO' => 'ID',
            'ILLINOIS' => 'IL', 'INDIANA' => 'IN', 'IOWA' => 'IA', 'KANSAS' => 'KS',
            'KENTUCKY' => 'KY', 'LOUISIANA' => 'LA', 'MAINE' => 'ME', 'MARYLAND' => 'MD',
            'MASSACHUSETTS' => 'MA', 'MICHIGAN' => 'MI', 'MINNESOTA' => 'MN', 'MISSISSIPPI' => 'MS',
            'MISSOURI' => 'MO', 'MONTANA' => 'MT', 'NEBRASKA' => 'NE', 'NEVADA' => 'NV',
            'NEW HAMPSHIRE' => 'NH', 'NEW JERSEY' => 'NJ', 'NEW MEXICO' => 'NM', 'NEW YORK' => 'NY',
            'NORTH CAROLINA' => 'NC', 'NORTH DAKOTA' => 'ND', 'OHIO' => 'OH', 'OKLAHOMA' => 'OK',
            'OREGON' => 'OR', 'PENNSYLVANIA' => 'PA', 'RHODE ISLAND' => 'RI', 'SOUTH CAROLINA' => 'SC',
            'SOUTH DAKOTA' => 'SD', 'TENNESSEE' => 'TN', 'TEXAS' => 'TX', 'UTAH' => 'UT',
            'VERMONT' => 'VT', 'VIRGINIA' => 'VA', 'WASHINGTON' => 'WA', 'WEST VIRGINIA' => 'WV',
            'WISCONSIN' => 'WI', 'WYOMING' => 'WY', 'DISTRICT OF COLUMBIA' => 'DC',
            'US/NATIONWIDE' => 'US',
        );

        $upper = strtoupper( trim( $state ) );

        // Already a 2-letter code?
        if ( strlen( $upper ) === 2 ) {
            return $upper;
        }

        return isset( $map[ $upper ] ) ? $map[ $upper ] : sanitize_text_field( $state );
    }

    /* ================================================================
       BULK GEOCODER — Add lat/lng to clubs missing coordinates
       ================================================================ */

    /**
     * AJAX: Scan for clubs missing coordinates
     */
    public function ajax_geocode_scan() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        // Get all published run_clubs
        $all_clubs = get_posts( array(
            'post_type'      => 'run_club',
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        $missing = array();

        foreach ( $all_clubs as $post_id ) {
            $lat = get_post_meta( $post_id, '_nxtrunn_latitude', true );
            $lng = get_post_meta( $post_id, '_nxtrunn_longitude', true );

            if ( empty( $lat ) || empty( $lng ) ) {
                $missing[] = array(
                    'id'    => $post_id,
                    'title' => get_the_title( $post_id ),
                );
            }
        }

        wp_send_json_success( array( 'clubs' => $missing, 'total' => count( $all_clubs ), 'missing' => count( $missing ) ) );
    }

    /**
     * AJAX: Geocode a single club
     */
    public function ajax_geocode_single() {

        check_ajax_referer( 'nxtrunn_import_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
        }

        $city    = get_post_meta( $post_id, '_nxtrunn_city', true );
        $state   = get_post_meta( $post_id, '_nxtrunn_state', true );
        $country = get_post_meta( $post_id, '_nxtrunn_country', true );

        if ( empty( $city ) && empty( $state ) ) {
            wp_send_json_success( array( 'skipped' => true, 'message' => 'No city or state' ) );
        }

        // Use the existing geocoding class
        $geocoder = new NXTRUNN_Geocoding();
        $coords = $geocoder->geocode_address( array(
            'city'        => $city ?: '',
            'state'       => $state ?: '',
            'postal_code' => '',
            'country'     => $country ?: 'US',
        ) );

        if ( ! $coords ) {
            wp_send_json_error( array( 'message' => 'Geocoding failed for: ' . $city . ', ' . $state ) );
        }

        update_post_meta( $post_id, '_nxtrunn_latitude', $coords['lat'] );
        update_post_meta( $post_id, '_nxtrunn_longitude', $coords['lng'] );

        wp_send_json_success( array(
            'lat'     => $coords['lat'],
            'lng'     => $coords['lng'],
            'skipped' => false,
        ) );
    }
}
