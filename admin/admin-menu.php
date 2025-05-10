<?php

function feedback_form_admin_menu() {
    add_menu_page(
        'Feedback',       
        'Feedback List',        
        'manage_options',                  
        'feedback-form-admin',        
        'feedback_form_admin_page', 
        'dashicons-bell',                  
        25                                
    );
}

function feedback_form_admin_page() {
    ?>
    <div class="wrap">
        <h1>Feedback List</h1>
        <p>This is where you could configure settings for your popup if you want.</p>
        <!-- You could add options, checkboxes, settings form here -->
    </div>
    <?php
}

add_action('admin_menu', 'feedback_form_admin_menu');

