<?php
/**
 * Handle email notifications
 */
class NXTRUNN_Email_Notifications {
    
    /**
     * Send verification email to admin
     */
    public function send_admin_verification_email( $post_id ) {
        
        $admin_email = get_option( 'nxtrunn_admin_email', get_option( 'admin_email' ) );
        $club_name = get_the_title( $post_id );
        $edit_link = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
        
        $city = get_post_meta( $post_id, '_nxtrunn_city', true );
        $state = get_post_meta( $post_id, '_nxtrunn_state', true );
        $submitter_email = get_post_meta( $post_id, '_nxtrunn_submitter_email', true );
        $admin_note = get_post_meta( $post_id, '_nxtrunn_admin_note', true );
        $is_woman_run = get_post_meta( $post_id, '_nxtrunn_is_woman_run', true );
        $is_bipoc_owned = get_post_meta( $post_id, '_nxtrunn_is_bipoc_owned', true );
        
        $badges = array();
        if ( $is_woman_run === '1' ) $badges[] = 'Woman-Run';
        if ( $is_bipoc_owned === '1' ) $badges[] = 'BIPOC-Owned';
        
        $subject = 'New Club Needs Badge Verification - ' . $club_name;
        
        $message = "Hey there!\n\n";
        $message .= "A new club submission needs your review:\n\n";
        $message .= "Club Name: " . $club_name . "\n";
        $message .= "Location: " . $city . ", " . $state . "\n";
        $message .= "Submitted by: " . $submitter_email . "\n\n";
        $message .= "Badges Requested:\n";
        $message .= implode( "\n", array_map( function($b) { return "✓ " . $b; }, $badges ) ) . "\n\n";
        
        if ( $admin_note ) {
            $message .= "Admin Note from Submitter:\n";
            $message .= $admin_note . "\n\n";
        } else {
            $message .= "No admin note provided.\n\n";
        }
        
        $message .= "Review & Approve:\n" . $edit_link . "\n\n";
        $message .= "---\n";
        $message .= "NXTRUNN Run Club Directory";
        
        wp_mail( $admin_email, $subject, $message );
    }
    
    /**
     * Send pending notification to submitter
     */
    public function send_submitter_pending_email( $email, $club_name ) {
        
        $subject = 'Your Club Submission is Under Review';
        
        $message = "Hi there!\n\n";
        $message .= "Thanks for submitting " . $club_name . " to the NXTRUNN directory!\n\n";
        $message .= "Your club is currently under review because you've applied for one or more diversity badges. ";
        $message .= "We'll verify and approve your listing within 2-3 business days.\n\n";
        $message .= "You'll receive another email once your club is live.\n\n";
        $message .= "Questions? Reply to this email.\n\n";
        $message .= "Keep running,\n";
        $message .= "The NXTRUNN Team";
        
        wp_mail( $email, $subject, $message );
    }
    
    /**
     * Send approval notification to submitter
     */
    public function send_submitter_approved_email( $email, $club_name, $post_id ) {
        
        $club_url = get_permalink( $post_id );
        
        $subject = 'Your Club is Live! 🎉';
        
        $message = "Hi there!\n\n";
        $message .= "Great news! " . $club_name . " is now live on the NXTRUNN directory.\n\n";
        $message .= "View Your Club:\n" . $club_url . "\n\n";
        $message .= "Want to make updates? Contact us at support@nxtrunn.com\n\n";
        $message .= "Thanks for being part of the NXTRUNN community!\n\n";
        $message .= "Keep running,\n";
        $message .= "The NXTRUNN Team";
        
        wp_mail( $email, $subject, $message );
    }
    
    /**
     * Send approval notification after admin approval
     */
    public function send_approval_notification( $post_id ) {
        
        $email = get_post_meta( $post_id, '_nxtrunn_submitter_email', true );
        $club_name = get_the_title( $post_id );
        
        if ( $email ) {
            $this->send_submitter_approved_email( $email, $club_name, $post_id );
        }
    }
}