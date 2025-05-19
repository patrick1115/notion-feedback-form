<?php
/**
 * Class NotionAPI
 * 
 * Handles all interactions with the Notion API
 */
class NotionAPI {
    private $api_key;
    private $database_id;
    private $api_version = '2022-06-28';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = NFF_NOTION_API_KEY;
        $this->database_id = NFF_NOTION_DATABASE_ID;
    }
    
    /**
     * Submit feedback to Notion
     * 
     * @param array $feedback_data Submission data
     * @return array Response data
     */
    public function submit_feedback($feedback_data) {
        $name = sanitize_text_field($feedback_data['name']);
        $feedback = sanitize_textarea_field($feedback_data['feedback']);
        $firstName = sanitize_text_field($feedback_data['first_name']);
        $lastName = sanitize_text_field($feedback_data['last_name']);
        $date = date('Y-m-d');
        
        $payload = [
            "parent" => ["database_id" => $this->database_id],
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
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Notion-Version' => $this->api_version,
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'message' => 'Thanks for your feedback!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Notion API error (' . $code . '): ' . $body
            ];
        }
    }
    
    /**
     * Update upvotes for a page
     * 
     * @param string $page_id Notion page ID
     * @return array Response data
     */
    public function update_upvotes($page_id) {
        // Get current page data from Notion
        $response = wp_remote_get("https://api.notion.com/v1/pages/$page_id", [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Notion-Version' => $this->api_version,
                'Content-Type' => 'application/json',
            ]
        ]);
        
        if (is_wp_error($response)) {
            error_log('Notion GET failed: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'Failed to fetch page from Notion'
            ];
        }
        
        $pageData = json_decode(wp_remote_retrieve_body($response), true);
        
        // Get current upvotes or default to 0
        $currentVotes = isset($pageData['properties']['Upvotes']['number'])
            ? $pageData['properties']['Upvotes']['number']
            : 0;
        
        $newVotes = $currentVotes + 1;
        
        // Send PATCH to update upvotes
        $patch = wp_remote_request("https://api.notion.com/v1/pages/$page_id", [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Notion-Version' => $this->api_version,
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
            return [
                'success' => false,
                'message' => 'Failed to update Notion'
            ];
        }
        
        return [
            'success' => true,
            'upvotes' => $newVotes
        ];
    }
    
    /**
     * Get feedback from Notion database
     * 
     * @return array Feedback items
     */
    public function get_feedback_items() {
        $response = wp_remote_post("https://api.notion.com/v1/databases/{$this->database_id}/query", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Notion-Version' => $this->api_version,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                "sorts" => [[ "timestamp" => "created_time", "direction" => "descending" ]]
            ])
        ]);
        
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data['results'])) {
            return [];
        }
        
        return $data['results'];
    }
}
