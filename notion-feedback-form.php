<?php
/**
 * Plugin Name: Notion Feedback Form
 * Description: Simple feedback form that sends entries to Notion via the Notion API.
 * Version: 1.0
 * Author: Patrick 
 */

// Replace these with Notion API credentials
define('NOTION_API_KEY', 'NOTION API KEY');
define('NOTION_DATABASE_ID', 'DATABASE ID');

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
                    feedback: feedback
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
    $date = date('Y-m-d'); 
    
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
    
/*
    if (is_wp_error($response)) {
        echo 'Submission failed.';
    } else {
        echo 'Thanks for your feedback!';
    }
*/    
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
                const countSpan = this.parentElement.querySelector('.nff-upvote-count');
                // instead of nextelementsibling
//                const countSpan = this.nextElementSibling;
                let currentVotes = parseInt(countSpan.textContent, 10);

                // Show vote instantly
                countSpan.textContent = currentVotes + 1;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'nff_upvote',
                        page_id: pageId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Upvote AJAX response:', data);
                    if (data.success) {
                        countSpan.textContent = data.upvotes;
                    } else {
                        countSpan.textContent = currentVotes;
                        alert('Failed to save upvote to Notion. Reason: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Upvote network error:', err);
                    countSpan.textContent = currentVotes;
                    alert('Network error during upvote.');
                });
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
    <form id="notion-feedback-form" style="max-width: 600px; margin-top: 1rem; font-family: sans-serif;">
        <label for="nff-name" style="display:block; margin-bottom: 5px;">Feedback Title</label>
        <input 
            type="text" 
            name="name" 
            id="nff-name" 
            placeholder="Brief Description of Feedback/Suggestion" 
            style="width:100%; margin-bottom:15px; padding:12px; font-size:16px; border-radius:6px; border:1px solid #ccc;">

        <label for="nff-feedback" style="display:block; margin-bottom: 5px;">Description</label>
        <textarea 
            name="feedback" 
            id="nff-feedback" 
            placeholder="Please leave feedback or suggestions in detail" 
            required 
            style="width:100%; height:180px; margin-bottom:15px; padding:14px; font-size:16px; border-radius:6px; border:1px solid #ccc; resize: vertical;"></textarea>

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

/*
function nff_form_shortcode() {
    ob_start(); ?>
    <form id="notion-feedback-form">
        <input type="text" name="name" placeholder="Feedback Header">
        <textarea name="feedback" placeholder="Enter feedback or suggestions here" required></textarea>
        <button type="submit">Submit</button>
    </form>
    <div id="nff-message"></div>
    <?php
    return ob_get_clean();
}
*/    
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

        echo '<div class="nff-comment" style="border:1px solid #ccc; border-radius:6px; padding:15px; display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">';

        echo '<div style="flex-grow:1;">';
        echo '<strong style="display:block; margin-bottom:5px; font-size:16px;">' . esc_html($name) . '</strong>';
        echo '<p style="margin:0; font-size:15px;">' . esc_html($feedback) . '</p>';
        echo '</div>';

        echo '<div class="nff-feedback-upvote" style="display:flex; flex-direction:column; align-items:center; gap:5px;">';
        echo '<button class="nff-upvote-btn" data-id="' . esc_attr($pageId) . '" style="font-size:20px; border:none; background:none; cursor:pointer;">↑</button>';
        echo '<span class="nff-upvote-count" style="font-size:14px;">' . intval($upvotes) . '</span>';
        echo '</div>';

        echo '</div>';
    }

    echo '</div>';
    /*
    echo '<div class="nff-feedback-list" style="display:flex; flex-direction:column; gap:20px; max-width:600px; margin-top:2rem;">';
    foreach ($data['results'] as $item) {
        $props = $item['properties'];

        $name = $props['Name']['title'][0]['text']['content'] ?? 'Anonymous';
        $feedback = $props['Feedback']['rich_text'][0]['text']['content'] ?? '';
        $upvotes = $props['Upvotes']['number'] ?? 0;

        $pageId = $item['id']; // will be used later for upvote functionality

        echo '<div class="nff-comment" style="border:1px solid #ccc; border-radius:6px; padding:15px; display:flex; justify-content:space-between; align-items:flex-start;">';

        echo '<div style="flex-grow:1;">';
        echo '<strong style="display:block; margin-bottom:5px; font-size:16px;">' . esc_html($name) . '</strong>';
        echo '<p style="margin:0; font-size:15px;">' . esc_html($feedback) . '</p>';
        echo '</div>';

        echo '<div style="display:flex; flex-direction:column; align-items:center; gap:5px;">';
        echo '<button class="nff-upvote-btn" data-id="' . esc_attr($pageId) . '" style="font-size:20px; border:none; background:none; cursor:pointer;">↑</button>';
        echo '<span style="font-size:14px;">' . intval($upvotes) . '</span>';
        echo '</div>';

        echo '</div>';
    }
    echo '</div>';
    */
    return ob_get_clean();
}
add_shortcode('notion_feedback_list', 'nff_display_feedback_shortcode');
