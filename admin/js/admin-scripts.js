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
    
});