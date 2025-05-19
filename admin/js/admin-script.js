/**
 * Admin JavaScript for Notion Feedback Form
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Future admin JS functionality will go here
        console.log('Notion Feedback Form admin script loaded');
        
        //Ex: click handler for feedback items
        $('.nff-admin-feedback-list table tbody tr').on('click', function() {
            // Could open details in the future
            console.log('Clicked feedback item');
        });
    });
    
})(jQuery);
