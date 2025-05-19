<?php
/**
 * Template for displaying feedback list
 * 
 * @package Notion Feedback Form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// $feedback_items is available from the calling function
?>

<div class="nff-filter-bar" style="margin-bottom: 20px;">
    <?php 
    $tags = ['All', 'New Request', 'Voting', 'Planning', 'In Progress', 'Testing', 'Complete'];
    foreach ($tags as $tag) {
        printf(
            '<button class="nff-filter-btn" data-tag="%s" style="margin-right:10px; padding:6px 12px; border:none; background-color:#eee; border-radius:4px; cursor:pointer;">%s</button>',
            esc_attr($tag),
            esc_html($tag)
        );
    }
    ?>
</div>

<div class="nff-feedback-list">
    <?php foreach ($feedback_items as $item) : 
        $props = $item['properties'];
        $name = isset($props['Name']['title'][0]['text']['content']) ? $props['Name']['title'][0]['text']['content'] : 'Anonymous';
        $feedback = isset($props['Feedback']['rich_text'][0]['text']['content']) ? $props['Feedback']['rich_text'][0]['text']['content'] : '';
        $upvotes = isset($props['Upvotes']['number']) ? $props['Upvotes']['number'] : 0;
        $page_id = $item['id'];
        
        // Get tag and style
        $tag = isset($props['Tags']['select']['name']) ? $props['Tags']['select']['name'] : '';
        $tag_style = $this->get_tag_style($tag);
    ?>
    
    <div class="nff-comment" data-tag="<?php echo esc_attr($tag ?: 'None'); ?>" style="border:1px solid #ccc; border-radius:6px; padding:15px; display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
        <div style="flex-grow:1;">
            <strong style="display:block; margin-bottom:5px; font-size:16px;"><?php echo esc_html($name); ?></strong>
            <p style="margin:0; font-size:15px;"><?php echo esc_html($feedback); ?></p>
        </div>
        
        <?php if (!empty($tag)) : ?>
            <span style="display:inline-block; top:10px; right:10px; display:inline-block; border-radius:12px; padding:4px 10px; font-size:12px; font-weight:500; <?php echo esc_attr($tag_style); ?>">
                <?php echo esc_html($tag); ?>
            </span>
        <?php endif; ?>
        
        <div class="nff-feedback-upvote" style="display:flex; flex-direction:column; align-items:center; gap:5px;">
            <button class="nff-upvote-btn" data-id="<?php echo esc_attr($page_id); ?>">↑</button>
            <span class="nff-upvote-count" style="font-size:14px;"><?php echo intval($upvotes); ?></span>
        </div>
    </div>
    
    <?php endforeach; ?>
</div>
