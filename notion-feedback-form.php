<?php
/**
 * Plugin Name: Notion Feedback Form
 * Description: Simple feedback form that sends entries to Notion via the Notion API. Features shortcode for listing the form and displaying the submissions. There is also an admin dashboard section for users to use. Connect Notion API and Database ID to get started. 
 * 
 * Version: 1.20
 * Author: Patrick 
 */

if (! defined('ABSPATH')) {
    exit;
}

// Replace these with Notion API credentials
define('NOTION_API_KEY', 'NOTION_API_KEY');
define('NOTION_DATABASE_ID', 'DATABASE_ID');
define('NFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NFF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NFF_VERSION', '1.20');

/*
 Class NotionFeedbackForm
 Main plugin class for initializing components and hooks
 */
class NotionFeedbackForm {
    //Constructor - Setup plugin hooks and filters: include = load files, register = hooks
    public function __construct() {
        $this->include_files();
        $this->register_hooks();
    }
    
    private function include_files() {
        require_once NFF_PLUGIN_DIR . 'admin/admin-menu.php';
        
        //Core functionality
        require_once NFF_PLUGIN_DIR . 'includes/class-notion-api.php';
        require_once NFF_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once NFF_PLUGIN_DIR . 'includes/class-feedback-display.php';
        require_once NFF_PLUGIN_DIR . 'includes/shortcodes.php';
    }
    
    //Register wp hooks
    private function register_hooks() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    //Enqueue frontend scripts and styles
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'nff-styles', 
            NFF_PLUGIN_URL . 'assets/css/nff-style.css',
            array(),
            NFF_VERSION
        );
        
        wp_enqueue_script(
            'nff-form',
            NFF_PLUGIN_URL . 'assets/js/nff-form.js',
            array('jquery'),
            NFF_VERSION,
            true
        );
        
        wp_enqueue_script(
            'nff-upvote',
            NFF_PLUGIN_URL . 'assets/js/nff-upvote.js',
            array('jquery'),
            NFF_VERSION,
            true
        );
        
        wp_enqueue_script(
            'nff-filter',
            NFF_PLUGIN_URL . 'assets/js/nff-filter.js',
            array('jquery'),
            NFF_VERSION,
            true
        );
        
        // Localize script with Ajax URL
        wp_localize_script('nff-form', 'nff_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nff_nonce')
        ));
        
        wp_localize_script('nff-upvote', 'nff_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nff_nonce')
        ));
    }
}

// Initialize plugin
$notion_feedback_form = new NotionFeedbackForm();

