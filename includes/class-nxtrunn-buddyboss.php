<?php
/**
 * BuddyBoss/BuddyPress integration
 */
class NXTRUNN_BuddyBoss {
    
    public function __construct() {
        
        // Only load if BuddyBoss/BuddyPress is active
        if ( ! function_exists('bp_is_active') && ! class_exists('BuddyBoss') ) {
            return;
        }
        
        // Hook into club approval
        add_action( 'nxtrunn_after_runclub_approved', array( $this, 'handle_group_assignment' ), 10, 1 );
        add_action( 'publish_run_club', array( $this, 'handle_group_assignment' ), 10, 1 );
        
        // Add badges to group names
        add_filter( 'bp_get_group_name', array( $this, 'add_badges_to_group_name' ), 10, 2 );
        
        // Add metabox for manual group selection
        add_action( 'add_meta_boxes', array( $this, 'add_group_metabox' ) );
        add_action( 'save_post_run_club', array( $this, 'save_group_metabox' ) );
    }
    
    /**
     * Handle group assignment when club is approved/published
     */
    public function handle_group_assignment( $post_id ) {
        
        // Check if already has a group assigned
        $existing_group_id = get_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', true );
        
        if ( $existing_group_id && groups_get_group( $existing_group_id ) ) {
            // Already has valid group, skip
            return;
        }
        
        $club_name = get_the_title( $post_id );
        
        // Try to find existing group by name
        $matched_group = $this->find_group_by_name( $club_name );
        
        if ( $matched_group ) {
            // Match found, link it
            update_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', $matched_group->id );
            groups_update_groupmeta( $matched_group->id, 'nxtrunn_club_id', $post_id );
            
            // Add note that it was matched
            update_post_meta( $post_id, '_nxtrunn_group_matched', '1' );
            
        } else {
            // No match, create new group
            $group_id = $this->create_group_for_club( $post_id );
            
            if ( $group_id ) {
                update_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', $group_id );
                groups_update_groupmeta( $group_id, 'nxtrunn_club_id', $post_id );
                update_post_meta( $post_id, '_nxtrunn_group_auto_created', '1' );
            }
        }
    }
    
    /**
     * Find existing group by name (fuzzy match)
     */
    private function find_group_by_name( $club_name ) {
        
        if ( ! function_exists('groups_get_groups') ) {
            return false;
        }
        
        // Normalize the club name for comparison
        $normalized_club = strtolower( trim( $club_name ) );
        
        // Get all groups (you might want to paginate this for large sites)
        $groups = groups_get_groups( array(
            'per_page' => 500,
            'show_hidden' => true
        ));
        
        if ( empty( $groups['groups'] ) ) {
            return false;
        }
        
        foreach ( $groups['groups'] as $group ) {
            $normalized_group = strtolower( trim( $group->name ) );
            
            // Exact match
            if ( $normalized_club === $normalized_group ) {
                return $group;
            }
            
            // Fuzzy match (contains)
            if ( strpos( $normalized_group, $normalized_club ) !== false ||
                 strpos( $normalized_club, $normalized_group ) !== false ) {
                return $group;
            }
        }
        
        return false;
    }
    
    /**
     * Create a new BuddyBoss group for the club
     */
    private function create_group_for_club( $post_id ) {
        
        if ( ! function_exists('groups_create_group') ) {
            return false;
        }
        
        $club_name = get_the_title( $post_id );
        $description = get_post_field( 'post_content', $post_id );
        $city = get_post_meta( $post_id, '_nxtrunn_city', true );
        $state = get_post_meta( $post_id, '_nxtrunn_state', true );
        
        // Create group
        $group_id = groups_create_group( array(
            'name'         => $club_name,
            'description'  => $description,
            'slug'         => sanitize_title( $club_name ),
            'status'       => 'public',
            'enable_forum' => true,
            'creator_id'   => get_current_user_id() ? get_current_user_id() : 1,
        ));
        
        if ( ! $group_id ) {
            return false;
        }
        
        // Set group location if available
        if ( $city && $state ) {
            groups_update_groupmeta( $group_id, 'bp_group_location', $city . ', ' . $state );
        }
        
        // Copy featured image to group avatar
        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $thumbnail_path = get_attached_file( $thumbnail_id );
            
            if ( $thumbnail_path && function_exists('bp_core_avatar_handle_upload') ) {
                // BuddyBoss avatar upload logic would go here
                // This is complex, so leaving as placeholder
            }
        }
        
        return $group_id;
    }
    
    /**
     * Add badges to group names in directory
     */
    public function add_badges_to_group_name( $name, $group ) {
        
        // Check if group has associated run club
        $club_id = groups_get_groupmeta( $group->id, 'nxtrunn_club_id', true );
        
        if ( $club_id ) {
            $badges = NXTRUNN_Badges::display_badges( $club_id );
            if ( $badges ) {
                $name .= ' ' . $badges;
            }
        }
        
        return $name;
    }
    
    /**
     * Add metabox for manual group selection/override
     */
    public function add_group_metabox() {
        
        add_meta_box(
            'nxtrunn_buddyboss_group',
            'BuddyBoss Group',
            array( $this, 'render_group_metabox' ),
            'run_club',
            'side',
            'default'
        );
    }
    
    public function render_group_metabox( $post ) {
        
        wp_nonce_field( 'nxtrunn_group_nonce', 'nxtrunn_group_nonce_field' );
        
        $group_id = get_post_meta( $post->ID, '_nxtrunn_buddyboss_group_id', true );
        $auto_created = get_post_meta( $post->ID, '_nxtrunn_group_auto_created', true );
        $matched = get_post_meta( $post->ID, '_nxtrunn_group_matched', true );
        
        ?>
        <div style="margin-bottom: 15px;">
            
            <?php if ( $group_id ) : ?>
                <?php
                $group = groups_get_group( $group_id );
                if ( $group ) :
                ?>
                    <p><strong>Connected Group:</strong></p>
                    <p>
                        <a href="<?php echo bp_get_group_permalink( $group ); ?>" target="_blank">
                            <?php echo esc_html( $group->name ); ?>
                        </a>
                    </p>
                    
                    <?php if ( $auto_created ) : ?>
                    <p style="color: #00a32a; font-size: 12px;">
                        ✓ Auto-created
                    </p>
                    <?php elseif ( $matched ) : ?>
                    <p style="color: #00a32a; font-size: 12px;">
                        ✓ Matched existing group
                    </p>
                    <?php endif; ?>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="nxtrunn_unlink_group" value="1">
                            Unlink this group
                        </label>
                    </p>
                <?php else : ?>
                    <p style="color: #d63638;">Group not found (may have been deleted)</p>
                <?php endif; ?>
                
            <?php else : ?>
                <p>No group connected yet.</p>
                <p style="font-size: 12px; color: #666;">
                    Group will be auto-created or matched when club is published.
                </p>
            <?php endif; ?>
            
        </div>
        
        <hr>
        
        <div>
            <p><strong>Manual Override:</strong></p>
            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                Select an existing group to link manually:
            </p>
            
            <select name="nxtrunn_manual_group_id" style="width: 100%;">
                <option value="">-- Select Group --</option>
                <?php
                if ( function_exists('groups_get_groups') ) {
                    $groups = groups_get_groups( array(
                        'per_page' => 500,
                        'show_hidden' => true
                    ));
                    
                    if ( ! empty( $groups['groups'] ) ) {
                        foreach ( $groups['groups'] as $group_option ) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                $group_option->id,
                                selected( $group_id, $group_option->id, false ),
                                esc_html( $group_option->name )
                            );
                        }
                    }
                }
                ?>
            </select>
        </div>
        <?php
    }
    
    public function save_group_metabox( $post_id ) {
        
        if ( ! isset( $_POST['nxtrunn_group_nonce_field'] ) ||
             ! wp_verify_nonce( $_POST['nxtrunn_group_nonce_field'], 'nxtrunn_group_nonce' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Handle unlinking
        if ( isset( $_POST['nxtrunn_unlink_group'] ) ) {
            $old_group_id = get_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', true );
            if ( $old_group_id ) {
                groups_delete_groupmeta( $old_group_id, 'nxtrunn_club_id' );
            }
            delete_post_meta( $post_id, '_nxtrunn_buddyboss_group_id' );
            delete_post_meta( $post_id, '_nxtrunn_group_auto_created' );
            delete_post_meta( $post_id, '_nxtrunn_group_matched' );
            return;
        }
        
        // Handle manual group selection
        if ( isset( $_POST['nxtrunn_manual_group_id'] ) && ! empty( $_POST['nxtrunn_manual_group_id'] ) ) {
            $new_group_id = intval( $_POST['nxtrunn_manual_group_id'] );
            
            // Remove old connection
            $old_group_id = get_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', true );
            if ( $old_group_id && $old_group_id != $new_group_id ) {
                groups_delete_groupmeta( $old_group_id, 'nxtrunn_club_id' );
            }
            
            // Add new connection
            update_post_meta( $post_id, '_nxtrunn_buddyboss_group_id', $new_group_id );
            groups_update_groupmeta( $new_group_id, 'nxtrunn_club_id', $post_id );
            
            // Mark as manually assigned
            delete_post_meta( $post_id, '_nxtrunn_group_auto_created' );
            delete_post_meta( $post_id, '_nxtrunn_group_matched' );
            update_post_meta( $post_id, '_nxtrunn_group_manual', '1' );
        }
    }
}