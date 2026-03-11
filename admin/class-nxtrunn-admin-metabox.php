<?php
/**
 * Verification metabox for pending clubs
 */
class NXTRUNN_Admin_Metabox {
    
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_verification_metabox' ) );
        add_action( 'admin_init', array( $this, 'handle_verification_action' ) );
    }
    
    public function add_verification_metabox() {
        
        global $post;
        
        if ( ! $post ) {
            return;
        }
        
        $needs_verification = get_post_meta( $post->ID, '_nxtrunn_needs_verification', true );
        
        if ( $needs_verification === '1' && get_post_status( $post->ID ) === 'pending' ) {
            
            add_meta_box(
                'nxtrunn_verification',
                '🏅 Badge Verification',
                array( $this, 'render_verification_metabox' ),
                'run_club',
                'side',
                'high'
            );
        }
    }
    
    public function render_verification_metabox( $post ) {
        
        $is_woman_run = get_post_meta( $post->ID, '_nxtrunn_is_woman_run', true );
        $is_bipoc_owned = get_post_meta( $post->ID, '_nxtrunn_is_bipoc_owned', true );
        $admin_note = get_post_meta( $post->ID, '_nxtrunn_admin_note', true );
        
        ?>
        <div class="nxtrunn-verification-box">
            
            <p><strong>Requested Badges:</strong></p>
            <ul>
                <?php if ( $is_woman_run === '1' ) : ?>
                <li>✓ Woman-Run</li>
                <?php endif; ?>
                <?php if ( $is_bipoc_owned === '1' ) : ?>
                <li>✓ BIPOC-Owned</li>
                <?php endif; ?>
            </ul>
            
            <?php if ( $admin_note ) : ?>
            <p><strong>Submitter Note:</strong></p>
            <p style="background: #f0f0f1; padding: 10px; border-radius: 4px;">
                <?php echo esc_html( $admin_note ); ?>
            </p>
            <?php endif; ?>
            
            <hr>
            
            <p><strong>Verification Actions:</strong></p>
            
            <form method="get" action="<?php echo admin_url('edit.php'); ?>" style="margin-bottom: 10px;">
                <input type="hidden" name="post_type" value="run_club">
                <input type="hidden" name="nxtrunn_action" value="approve">
                <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                <?php wp_nonce_field( 'nxtrunn_verify_' . $post->ID, 'nxtrunn_nonce' ); ?>
                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                    ✅ Approve & Publish
                </button>
            </form>
            
            <form method="get" action="<?php echo admin_url('edit.php'); ?>" style="margin-bottom: 10px;">
                <input type="hidden" name="post_type" value="run_club">
                <input type="hidden" name="nxtrunn_action" value="reject_badges">
                <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                <?php wp_nonce_field( 'nxtrunn_verify_' . $post->ID, 'nxtrunn_nonce' ); ?>
                <button type="submit" class="button button-secondary button-large" style="width: 100%;">
                    🚫 Reject Badges & Publish
                </button>
            </form>
            
            <form method="get" action="<?php echo admin_url('edit.php'); ?>" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                <input type="hidden" name="post_type" value="run_club">
                <input type="hidden" name="nxtrunn_action" value="delete">
                <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
                <?php wp_nonce_field( 'nxtrunn_verify_' . $post->ID, 'nxtrunn_nonce' ); ?>
                <button type="submit" class="button button-link-delete button-large" style="width: 100%;">
                    🗑️ Delete Submission
                </button>
            </form>
            
        </div>
        <?php
    }
    
    public function handle_verification_action() {
        
        if ( ! isset( $_GET['nxtrunn_action'] ) || ! isset( $_GET['post_id'] ) ) {
            return;
        }
        
        $post_id = intval( $_GET['post_id'] );
        $action = sanitize_text_field( $_GET['nxtrunn_action'] );
        
        if ( ! wp_verify_nonce( $_GET['nxtrunn_nonce'], 'nxtrunn_verify_' . $post_id ) ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        switch ( $action ) {
            
            case 'approve':
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_status' => 'publish'
                ));
                
                update_post_meta( $post_id, '_nxtrunn_needs_verification', '0' );
                update_post_meta( $post_id, '_nxtrunn_verified_by', get_current_user_id() );
                update_post_meta( $post_id, '_nxtrunn_verified_date', current_time( 'mysql' ) );
                
                $emailer = new NXTRUNN_Email_Notifications();
                $emailer->send_approval_notification( $post_id );
                
                do_action( 'nxtrunn_after_runclub_approved', $post_id );
                
                wp_redirect( admin_url( 'edit.php?post_type=run_club&approved=1' ) );
                exit;
                
            case 'reject_badges':
                update_post_meta( $post_id, '_nxtrunn_is_woman_run', '0' );
                update_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', '0' );
                update_post_meta( $post_id, '_nxtrunn_needs_verification', '0' );
                
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_status' => 'publish'
                ));
                
                $emailer = new NXTRUNN_Email_Notifications();
                $emailer->send_approval_notification( $post_id );
                
                wp_redirect( admin_url( 'edit.php?post_type=run_club&badges_rejected=1' ) );
                exit;
                
            case 'delete':
                wp_delete_post( $post_id, true );
                wp_redirect( admin_url( 'edit.php?post_type=run_club&deleted=1' ) );
                exit;
        }
    }
}