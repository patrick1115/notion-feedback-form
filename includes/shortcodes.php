<?php
//Register and handle all plugin shortcodes

if (!defined('ABSPATH')) {
    exit;
}

//Register all shortcodes
function nff_register_shortcodes() {
    add_shortcode('notion_feedback_form', 'nff_form_shortcode');
    add_shortcode('notion_feedback_list', 'nff_display_feedback_shortcode');
}
add_action('init', 'nff_register_shortcodes');

/**
 * Shortcode to display feedback form
 * 
 * @return string Form HTML
 */
function nff_form_shortcode() {
    $feedback_display = new FeedbackDisplay();
    return $feedback_display->get_feedback_form();
}

/**
 * Shortcode to display feedback list
 * 
 * @return string Feedback list HTML
 */
function nff_display_feedback_shortcode() {
    $feedback_display = new FeedbackDisplay();
    return $feedback_display->get_feedback_display();
}
