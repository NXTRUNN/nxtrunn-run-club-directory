jQuery(document).ready(function($) {
    
    'use strict';
    
    // Confirmation for deletion
    $('button[name="nxtrunn_verify_action"][value="delete"]').on('click', function(e) {
        if (!confirm('Are you sure you want to permanently delete this club submission? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Highlight pending clubs in admin list
    $('.column-verification span:contains("Needs Verification")').closest('tr').css('background-color', '#fff3cd');

    // Resend outreach email button
    $('#nxtrunn-resend-outreach').on('click', function() {
        var $btn = $(this);
        var $result = $('#nxtrunn-resend-result');
        var postId = $btn.data('post');

        $btn.prop('disabled', true).text('Sending...');
        $result.text('');

        $.post(ajaxurl, {
            action: 'nxtrunn_resend_outreach',
            post_id: postId,
            nonce: $('#nxtrunn_outreach_nonce_field').val() || $('input[name="nxtrunn_outreach_nonce_field"]').val()
        }, function(response) {
            if (response.success) {
                $result.css('color', '#5E9070').text(response.data);
                $btn.text('Sent!');
            } else {
                $result.css('color', '#C86848').text(response.data || 'Failed');
                $btn.prop('disabled', false).text('Resend Outreach Email');
            }
        }).fail(function() {
            $result.css('color', '#C86848').text('AJAX error');
            $btn.prop('disabled', false).text('Resend Outreach Email');
        });
    });

});