<?php
/**
 * Class FormHandler
 * 
 * Handles form submissions and AJAX requests
 */
class FormHandler {
    private $notion_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->notion_api = new NotionAPI();
        
        // Register AJAX handlers
        add_action('wp_ajax_nff_submit_feedback', array($this, 'handle_feedback_ajax'));
        add_action('wp_ajax_nopriv_nff_submit_feedback', array($this, 'handle_feedback_ajax'));
        
        add_action('wp_ajax_nff_upvote', array($this, 'handle_upvote_ajax'));
        add_action('wp_ajax_nopriv_nff_upvote', array($this, 'handle_upvote_ajax'));
    }
    
    //AJAX handler for form submissions
    public function handle_feedback_ajax() {
        // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nff_nonce')) {
        //     wp_send_json_error(['message' => 'Security check failed']);
        // }
        
        $feedback_data = array(
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'feedback' => isset($_POST['feedback']) ? $_POST['feedback'] : '',
            'first_name' => isset($_POST['first_name']) ? $_POST['first_name'] : '',
            'last_name' => isset($_POST['last_name']) ? $_POST['last_name'] : '',
        );
        
        $result = $this->notion_api->submit_feedback($feedback_data);
        
        if ($result['success']) {
            echo $result['message'];
        } else {
            echo 'Submission failed: ' . $result['message'];
        }
        
        wp_die();
    }
    
    //AJAX handler for upvotes
    public function handle_upvote_ajax() {
        // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nff_nonce')) {
        //     wp_send_json_error(['message' => 'Security check failed']);
        // }
        
        $page_id = sanitize_text_field($_POST['page_id']);
        if (!$page_id) {
            wp_send_json_error(['message' => 'Missing page ID']);
        }
        
        $result = $this->notion_api->update_upvotes($page_id);
        
        if ($result['success']) {
            wp_send_json_success(['upvotes' => $result['upvotes']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}

// Initialize the form handler
$form_handler = new FormHandler();
