<?php
/**
 * Plugin Name: Notion Feedback Form
 * Description: Simple feedback form that sends entries to Notion via the Notion API.
 *              Features: 
 *                  - First and last name added to form. 
 *                  - Upvote button color change on hover. 
 *                  - Restricts user from multiple upvotes per session. 
 *              Admin tab on the Dashboard
 * 
 * Version: 1.20
 * Author: Patrick 
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php'; 

// Replace these with Notion API credentials
define('NOTION_API_KEY', 'NOTION_API_KEY');
define('NOTION_DATABASE_ID', 'DATABASE_ID');

// Enqueue inline JavaScript for AJAX submission
function nff_enqueue_scripts() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('notion-feedback-form');
        const msgDiv = document.getElementById('nff-message');

        form?.addEventListener('submit', function (e) {
            e.preventDefault();
            const feedback = this.feedback.value.trim();
            if (!feedback) return;

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'nff_submit_feedback',
                    name: this.name.value, 
                    feedback: feedback,
                    first_name: this.first_name.value,
                    last_name: this.last_name.value
                })
            })
            .then(res => res.text())
            .then(msg => {
                msgDiv.textContent = msg;
                this.reset();
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'nff_enqueue_scripts');

// AJAX handler for form submissions
function nff_handle_feedback_ajax() {
    $name = sanitize_text_field($_POST['name']);
    $feedback = sanitize_textarea_field($_POST['feedback']);
    $firstName = sanitize_text_field($_POST['first_name']);
    $lastName = sanitize_text_field($_POST['last_name']);
    $date = date('Y-m-d'); // Notion expects ISO date format

    $payload = [
        "parent" => ["database_id" => NOTION_DATABASE_ID],
        "properties" => [
            "Name" => [
                "title" => [[
                    "text" => ["content" => $name ?: 'Anonymous']
                ]]
            ],
            "Feedback" => [
                "rich_text" => [[
                    "text" => ["content" => $feedback]
                ]]
            ],
            "Upvotes" => [
                "number" => 0
            ],
            "First Name" => [
                "rich_text" => [[
                    "text" => ["content" => $firstName]
                ]]
            ],
            "Last Name" => [
                "rich_text" => [[
                    "text" => ["content" => $lastName]
                ]]
            ],
            "Date Added" => [
                "date" => [
                    "start" => $date
                ]
            ]
        ]
    ];    

    $response = wp_remote_post('https://api.notion.com/v1/pages', [
        'body' => json_encode($payload),
        'headers' => [
            'Authorization' => 'Bearer ' . NOTION_API_KEY,
            'Content-Type' => 'application/json',
            'Notion-Version' => '2022-06-28',
        ]
    ]);

// error handler    
    if (is_wp_error($response)) {
        echo 'Submission failed: ' . $response->get_error_message();
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
    
        if ($code >= 200 && $code < 300) {
            echo 'Thanks for your feedback!';
        } else {
            echo 'Notion API error (' . $code . '): ' . $body;
        }
    }
    
    wp_die();
}
add_action('wp_ajax_nopriv_nff_submit_feedback', 'nff_handle_feedback_ajax');
add_action('wp_ajax_nff_submit_feedback', 'nff_handle_feedback_ajax');

function nff_upvote_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.nff-upvote-btn').forEach(button => {
            button.addEventListener('click', function () {
                const pageId = this.dataset.id;
                //const countSpan = this.parentElement.querySelector('.nff-upvote-count');
                  
                const countSpan = this.nextElementSibling;
                let currentVotes = parseInt(countSpan.textContent, 10);
                let newVotes = currentVotes + 1; 

                // Check if already voted
                // Record vote in localStorage and for unique key
                // key exists = blocked vote, not = stores key
                const voted = localStorage.getItem(`nff-voted-${pageId}`);
                if (voted) {
                    alert('You have already upvoted this comment.');
                    return;
                }

                localStorage.setItem(`nff-voted-${pageId}`, 'true');
                button.classList.add('voted'); 

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'nff_upvote',
                        page_id: pageId,
                        upvotes: newVotes
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Upvote AJAX response:', data);
                    if (data.success) {
                        countSpan.textContent = data.upvotes;
                    } else {
                        countSpan.textContent = currentVotes;
                        button.classList.remove('voted'); 
                        alert('Failed to save upvote to Notion. Reason: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Upvote network error:', err);
                    countSpan.textContent = currentVotes;
                    button.classList.remove('voted'); 
                    localStorage.removeItem(voted); 
                    alert('Network error during upvote.');
                });
                countSpan.textContent = newVotes;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'nff_upvote_script');

function nff_upvote_handler() {
    $pageId = sanitize_text_field($_POST['page_id']);
    if (!$pageId) {
        wp_send_json_error(['message' => 'Missing page ID']);
    }

    $notionApiKey = NOTION_API_KEY;

    // Get current page data from Notion
    $response = wp_remote_get("https://api.notion.com/v1/pages/$pageId", [
        'headers' => [
            'Authorization' => "Bearer $notionApiKey",
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json',
        ]
    ]);

    if (is_wp_error($response)) {
        error_log('Notion GET failed: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'Failed to fetch page from Notion']);
    }

    $pageData = json_decode(wp_remote_retrieve_body($response), true);

    // Get current upvotes or default to 0
    $currentVotes = isset($pageData['properties']['Upvotes']['number'])
        ? $pageData['properties']['Upvotes']['number']
        : 0;

    $newVotes = $currentVotes + 1;

    // Send PATCH to update upvotes
    $patch = wp_remote_request("https://api.notion.com/v1/pages/$pageId", [
        'method' => 'PATCH',
        'headers' => [
            'Authorization' => "Bearer $notionApiKey",
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'properties' => [
                'Upvotes' => [
                    'number' => $newVotes
                ]
            ]
        ])
    ]);

    if (is_wp_error($patch)) {
        error_log('Notion PATCH failed: ' . $patch->get_error_message());
        wp_send_json_error(['message' => 'Failed to update Notion']);
    }

    wp_send_json_success(['upvotes' => $newVotes]);
}
add_action('wp_ajax_nff_upvote', 'nff_upvote_handler');
add_action('wp_ajax_nopriv_nff_upvote', 'nff_upvote_handler');

// Shortcode to output the form
function nff_form_shortcode() {
    ob_start(); ?>
    <form id="notion-feedback-form" style="max-width: 800px; margin-top: 1rem; font-family: sans-serlast_if;">
        <div style="display: flex; gap: 12px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <input 
                    type="text" 
                    name="first_name" 
                    id="nff-firstName" 
                    placeholder="First Name"
                    required
                    style="width: 80%; padding:10px; font-size:16px; border:1px solid #ccc; border-radius:4px;"
                >
            </div>
            <div style="flex: 1;">
                <input 
                    type="text" 
                    name="last_name" 
                    id="nff-lastName" 
                    placeholder="Last Name"
                    required
                    style="width: 80%; padding:10px; font-size:16px; border:1px solid #ccc; border-radius:4px;"
                >
            </div>
        </div>

        <label for="nff-name" style="display:block; margin-bottom: 5px;">Feedback Title</label>
            <input 
                type="text" 
                name="name" 
                id="nff-name" 
                placeholder="Brief Description of Feedback/Suggestion" 
                required
                style="width:100%; margin-bottom:15px; padding:12px; font-size:16px; border-radius:6px; border:1px solid #ccc;"
                >

        <label for="nff-feedback" style="display:block; margin-bottom: 5px;">Description</label>
            <textarea 
                name="feedback" 
                id="nff-feedback" 
                placeholder="Please leave feedback or suggestions in detail" 
                required 
                style="width:100%; height:180px; margin-bottom:15px; padding:14px; font-size:16px; border-radius:6px; border:1px solid #ccc; resize: vertical;"
                >
            </textarea>

        <button 
            type="submit" 
            style="padding:12px 24px; font-size:16px; border:none; background-color:#0073aa; color:white; border-radius:6px; cursor:pointer;">
            Submit
        </button>

        <div id="nff-message" style="margin-top:15px; font-weight:500;"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('notion_feedback_form', 'nff_form_shortcode');

//display feedback
function nff_display_feedback_shortcode() {
    $apiKey = NOTION_API_KEY;
    $databaseId = NOTION_DATABASE_ID;

    $response = wp_remote_post("https://api.notion.com/v1/databases/$databaseId/query", [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Notion-Version' => '2022-06-28',
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            "sorts" => [[ "timestamp" => "created_time", "direction" => "descending" ]]
        ])
    ]);

    if (is_wp_error($response)) {
        return '<p>Error fetching feedback.</p>';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['results'])) {
        return '<p>No feedback submitted yet.</p>';
    }

    ob_start();

    echo '<div class="nff-feedback-list">';

    foreach ($data['results'] as $item) {
        $props = $item['properties'];
        $name = $props['Name']['title'][0]['text']['content'] ?? 'Anonymous';
        $feedback = $props['Feedback']['rich_text'][0]['text']['content'] ?? '';
        $upvotes = $props['Upvotes']['number'] ?? 0;

        $pageId = $item['id'];
          //tags and color based on notion's color
        $tag = $props['Tags']['select']['name'] ?? '';
        $tagStyle = '';
        switch ($tag) {
            case 'New Request':
                $tagStyle = 'background-color:#1b75bc; color:#fff;'; // Blue
                break;
            case 'Voting':
                $tagStyle = 'background-color:#e7bb37; color:#fff;'; // Yellow
                break;
            case 'Complete':
                $tagStyle = 'background-color:#22c55e; color:#fff;'; // Green
                break;
            case 'Planning': 
                $tagStyle = 'background-color:#ef404a; color:#fff;'; // Red
                break;
            case 'In Progress':
                $tagStyle = 'background-color:#91ca6b; color:#fff;'; //AO Green
                break;
            case 'Testing':
                $tagStyle = 'background-color:#83b4da; color:#fff;'; //Medium blue
                break;
            default:
                $tagStyle = 'background-color:#e5e7eb; color:#fff;'; // Light gray 
                break;
        } 
        echo '<div class="nff-comment" style="border:1px solid #ccc; border-radius:6px; padding:15px; display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">';

        echo '<div style="flex-grow:1;">';
        echo '<strong style="display:block; margin-bottom:5px; font-size:16px;">' . esc_html($name) . '</strong>';
        echo '<p style="margin:0; font-size:15px;">' . esc_html($feedback) . '</p>';
        echo '</div>';
        
        if (!empty($tag)) {
            echo '<span style="display:inline-block; top:10px; right:10px; display:inline-block; border-radius:12px; padding:4px 10px; font-size:12px; font-weight:500; ' . esc_attr($tagStyle) . '">' . esc_html($tag) . '</span>';
        }
        echo '<div class="nff-feedback-upvote" style="display:flex; flex-direction:column; align-items:center; gap:5px;">';
        echo '<button class="nff-upvote-btn" data-id="' . esc_attr($pageId) . '">↑</button>';
        echo '<span class="nff-upvote-count" style="font-size:14px;">' . intval($upvotes) . '</span>';
        echo '</div>';
        
        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}
add_shortcode('notion_feedback_list', 'nff_display_feedback_shortcode');

function nff_enqueue_styles() {
    wp_enqueue_style('nff-style', plugin_dir_url(__FILE__) . 'styles/nff-style.css');
}
add_action('wp_enqueue_scripts', 'nff_enqueue_styles');

