<?php
/**
 * Admin Menu Setup
 * 
 * @package Notion Feedback Form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NFF_Admin
 * 
 * Handles admin menu setup and admin pages
 */
class NFF_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    //Add admin menu pages
    public function add_admin_menu() {
        add_menu_page(
            __('Feedback', 'notion-feedback-form'),
            __('Feedback', 'notion-feedback-form'),
            'manage_options',
            'notion-feedback-form',
            array($this, 'render_admin_dashboard'),
            'dashicons-bell',
            25
        );
        
        add_submenu_page(
            'notion-feedback-form',
            __('Dashboard', 'notion-feedback-form'),
            __('Dashboard', 'notion-feedback-form'),
            'manage_options',
            'notion-feedback-form',
            array($this, 'render_admin_dashboard')
        );
        
        add_submenu_page(
            'notion-feedback-form',
            __('Settings', 'notion-feedback-form'),
            __('Settings', 'notion-feedback-form'),
            'manage_options',
            'notion-feedback-settings',
            array($this, 'render_settings_page')
        );
    }
    
    //Enqueue admin assets
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'notion-feedback') === false) {
            return;
        }
        
        wp_enqueue_style(
            'nff-admin-style',
            NFF_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            NFF_VERSION
        );
        
        wp_enqueue_script(
            'nff-admin-script',
            NFF_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            NFF_VERSION,
            true
        );
    }
    
    public function render_admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Notion Feedback Dashboard', 'notion-feedback-form'); ?></h1>
            
            <div class="nff-admin-container">
                <div class="nff-admin-card">
                    <h2><?php _e('Recent Feedback', 'notion-feedback-form'); ?></h2>
                    <p><?php _e('View and manage recent feedback submissions.', 'notion-feedback-form'); ?></p>
                    
                    <div class="nff-admin-feedback-list">
                        <?php 
                        // Get feedback items from Notion API
                        $notion_api = new NotionAPI();
                        $items = $notion_api->get_feedback_items();
                        
                        if (empty($items)) {
                            echo '<p>' . __('No feedback items found.', 'notion-feedback-form') . '</p>';
                        } else {
                            echo '<table class="widefat fixed">';
                            echo '<thead><tr>';
                            echo '<th>' . __('Name', 'notion-feedback-form') . '</th>';
                            echo '<th>' . __('Feedback', 'notion-feedback-form') . '</th>';
                            echo '<th>' . __('Status', 'notion-feedback-form') . '</th>';
                            echo '<th>' . __('Votes', 'notion-feedback-form') . '</th>';
                            echo '</tr></thead>';
                            echo '<tbody>';
                            
                            // Limit to 10 items for performance
                            $count = 0;
                            foreach ($items as $item) {
                                if ($count >= 10) break;
                                
                                $props = $item['properties'];
                                $name = isset($props['Name']['title'][0]['text']['content']) ? $props['Name']['title'][0]['text']['content'] : 'Anonymous';
                                $feedback = isset($props['Feedback']['rich_text'][0]['text']['content']) ? $props['Feedback']['rich_text'][0]['text']['content'] : '';
                                $tag = isset($props['Tags']['select']['name']) ? $props['Tags']['select']['name'] : 'New';
                                $upvotes = isset($props['Upvotes']['number']) ? $props['Upvotes']['number'] : 0;
                                
                                echo '<tr>';
                                echo '<td>' . esc_html($name) . '</td>';
                                echo '<td>' . esc_html(substr($feedback, 0, 100)) . (strlen($feedback) > 100 ? '...' : '') . '</td>';
                                echo '<td>' . esc_html($tag) . '</td>';
                                echo '<td>' . intval($upvotes) . '</td>';
                                echo '</tr>';
                                
                                $count++;
                            }
                            
                            echo '</tbody></table>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="nff-admin-sidebar">
                    <div class="nff-admin-card">
                        <h3><?php _e('Plugin Information', 'notion-feedback-form'); ?></h3>
                        <p><?php _e('Version:', 'notion-feedback-form'); ?> <?php echo NFF_VERSION; ?></p>
                        <p><?php _e('Use shortcodes to display the form and feedback list on your site.', 'notion-feedback-form'); ?></p>
                        <ul>
                            <li><code>[notion_feedback_form]</code> - <?php _e('Display the feedback form', 'notion-feedback-form'); ?></li>
                            <li><code>[notion_feedback_list]</code> - <?php _e('Display the feedback list', 'notion-feedback-form'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Notion Feedback Settings', 'notion-feedback-form'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                // For future implementation of settings
                // settings_fields('nff_settings');
                // do_settings_sections('nff_settings');
                ?>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e('Notion API Key', 'notion-feedback-form'); ?></th>
                        <td>
                            <input type="text" class="regular-text" value="<?php echo esc_attr(NFF_NOTION_API_KEY); ?>" disabled />
                            <p class="description"><?php _e('To change the API key, edit the plugin constants in the main file.', 'notion-feedback-form'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Notion Database ID', 'notion-feedback-form'); ?></th>
                        <td>
                            <input type="text" class="regular-text" value="<?php echo esc_attr(NFF_NOTION_DATABASE_ID); ?>" disabled />
                            <p class="description"><?php _e('To change the Database ID, edit the plugin constants in the main file.', 'notion-feedback-form'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p><?php _e('Future versions will include more settings options.', 'notion-feedback-form'); ?></p>
                
                <?php // submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize admin
$nff_admin = new NFF_Admin();
