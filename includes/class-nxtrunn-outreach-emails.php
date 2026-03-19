<?php
/**
 * Outreach email sending — first contact + Day-7 follow-up
 */
class NXTRUNN_Outreach_Emails {

    /**
     * Send the first outreach email to a club
     */
    public static function send_outreach( $post_id, $email ) {
        $club_name = get_the_title( $post_id );
        $claim_url = self::get_claim_url( $post_id );

        $subject = 'Your run club is on NXTRUNN';
        $html    = self::render_outreach_template( $club_name, $claim_url );
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Treb at NXTRUNN <treb@nxtrunn.com>',
        );

        wp_mail( $email, $subject, $html, $headers );
    }

    /**
     * Send the Day-7 follow-up email
     */
    public static function send_followup( $post_id ) {
        // Don't send if already claimed
        if ( get_post_meta( $post_id, '_nxtrunn_claimed', true ) === '1' ) return;
        // Don't send if already sent
        if ( get_post_meta( $post_id, '_nxtrunn_followup_sent', true ) ) return;

        $email = get_post_meta( $post_id, '_nxtrunn_outreach_email', true );
        if ( ! $email ) return;

        $club_name = get_the_title( $post_id );
        $claim_url = self::get_claim_url( $post_id );

        $subject = 'Just checking in - ' . $club_name . ' on NXTRUNN';
        $html    = self::render_followup_template( $club_name, $claim_url );
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Treb at NXTRUNN <treb@nxtrunn.com>',
        );

        wp_mail( $email, $subject, $html, $headers );
        update_post_meta( $post_id, '_nxtrunn_followup_sent', time() );
    }

    /**
     * Build the claim URL for a club
     */
    private static function get_claim_url( $post_id ) {
        // Directory page with open_claim param triggers the claim modal
        $directory_page_id = get_option( 'nxtrunn_directory_page_id' );
        $base = $directory_page_id ? get_permalink( $directory_page_id ) : home_url( '/run-club-directory-2/' );
        return add_query_arg( 'open_claim', $post_id, $base );
    }

    /**
     * Outreach email HTML template
     */
    private static function render_outreach_template( $club_name, $claim_url ) {
        $logo_url = NXTRUNN_PLUGIN_URL . 'assets/images/logo.png';

        return '<!DOCTYPE html>
<html>
<body style="font-family: \'DM Sans\', Arial, sans-serif; color: #1C1720; max-width: 600px; margin: 0 auto; padding: 32px 24px; background: #F7F4F9;">
  <div style="background: #fff; border-radius: 12px; padding: 32px 24px;">

  <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Hey ' . esc_html( $club_name ) . ' fam,</p>

  <p>I found your club while building something I wish existed when I started running &mdash; and I had to reach out.</p>

  <p>My name is Treb. I built <strong>NXTRUNN</strong> because run clubs, especially ones led by Black, Brown, and women runners, deserve more visibility. Not just a hashtag &mdash; a real home where people can find you, follow your journey, and show up to your runs.</p>

  <p><strong>Your club is already listed. But I want YOU to own it.</strong></p>

  <p>Claiming your listing takes 2 minutes. Once you do, you can update your description, add your meeting spot, link your socials, and make sure the right runners find you.</p>

  <p>It&rsquo;s free. Always will be.</p>

  <p style="margin: 32px 0; text-align: center;">
    <a href="' . esc_url( $claim_url ) . '"
       style="display: inline-block; background: #1C1720; color: #fff; padding: 14px 28px; border-radius: 9999px; text-decoration: none; font-weight: 600; font-size: 15px;">
      Claim Your Club &rarr;
    </a>
  </p>

  <p>Let&rsquo;s build this community together.</p>

  <p>&mdash; Treb<br>Founder, NXTRUNN</p>

  <p style="font-size: 13px; color: #6D6070; margin-top: 32px; border-top: 1px solid #E2DAE8; padding-top: 16px;">
    P.S. If this isn&rsquo;t the right contact for your club, feel free to forward it to whoever runs things. We want the right people in the room.
  </p>

  </div>
</body>
</html>';
    }

    /**
     * Follow-up email HTML template
     */
    private static function render_followup_template( $club_name, $claim_url ) {
        return '<!DOCTYPE html>
<html>
<body style="font-family: \'DM Sans\', Arial, sans-serif; color: #1C1720; max-width: 600px; margin: 0 auto; padding: 32px 24px; background: #F7F4F9;">
  <div style="background: #fff; border-radius: 12px; padding: 32px 24px;">

  <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Hey ' . esc_html( $club_name ) . ',</p>

  <p>Just wanted to make sure my last email didn&rsquo;t get buried in the inbox.</p>

  <p>Your listing on NXTRUNN is live &mdash; runners are already searching for clubs like yours. I just want to make sure the right person is behind it.</p>

  <p>Claiming takes 2 minutes and it&rsquo;s completely free. Once you do, you control everything &mdash; your description, your meeting spot, your socials, your logo.</p>

  <p>No account? No problem. The claim flow walks you through a quick setup and gets you in.</p>

  <p style="margin: 32px 0; text-align: center;">
    <a href="' . esc_url( $claim_url ) . '"
       style="display: inline-block; background: #1C1720; color: #fff; padding: 14px 28px; border-radius: 9999px; text-decoration: none; font-weight: 600; font-size: 15px;">
      Claim ' . esc_html( $club_name ) . ' &rarr;
    </a>
  </p>

  <p>If now&rsquo;s not the right time, no worries at all. Your listing stays up either way &mdash; I just want the community to hear from you directly.</p>

  <p>&mdash; Treb<br>Founder, NXTRUNN</p>

  </div>
</body>
</html>';
    }
}
