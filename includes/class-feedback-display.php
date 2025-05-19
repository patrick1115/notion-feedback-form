<?php
/**
 * Class FeedbackDisplay
 * 
 * Handles displaying feedback data from Notion
 */
class FeedbackDisplay {
    private $notion_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->notion_api = new NotionAPI();
    }
    
    /**
     * Get HTML for displaying feedback list
     * 
     * @return string HTML output
     */
    public function get_feedback_display() {
        $feedback_items = $this->notion_api->get_feedback_items();
        
        if (isset($feedback_items['error'])) {
            return '<p>Error fetching feedback: ' . esc_html($feedback_items['error']) . '</p>';
        }
        
        if (empty($feedback_items)) {
            return '<p>No feedback submitted yet.</p>';
        }
        
        ob_start();
        
        // Include template file with filter buttons
        include NFF_PLUGIN_DIR . 'templates/feedback-list.php';
        
        return ob_get_clean();
    }
    
    /**
     * Get HTML for the feedback form
     * 
     * @return string HTML output
     */
    public function get_feedback_form() {
        ob_start();
        
        // Include template file
        include NFF_PLUGIN_DIR . 'templates/feedback-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Get tag color style based on tag name
     * 
     * @param string $tag Tag name
     * @return string CSS style
     */
    public function get_tag_style($tag) {
        switch ($tag) {
            case 'New Request':
                return 'background-color:#1b75bc; color:#fff;'; // Blue
            case 'Voting':
                return 'background-color:#e7bb37; color:#fff;'; // Yellow
            case 'Complete':
                return 'background-color:#22c55e; color:#fff;'; // Green
            case 'Planning': 
                return 'background-color:#ef404a; color:#fff;'; // Red
            case 'In Progress':
                return 'background-color:#91ca6b; color:#fff;'; // AO Green
            case 'Testing':
                return 'background-color:#83b4da; color:#fff;'; // Medium blue
            default:
                return 'background-color:#e5e7eb; color:#fff;'; // Light gray 
        }
    }
}
