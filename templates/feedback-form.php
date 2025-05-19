<?php
/**
 * Template for the feedback form
 * 
 * @package Notion Feedback Form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<form id="notion-feedback-form" style="max-width: 800px; margin-top: 1rem; font-family: sans-serif;">
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
        ></textarea>

    <button 
        type="submit" 
        style="padding:12px 24px; font-size:16px; border:none; background-color:#0073aa; color:white; border-radius:6px; cursor:pointer;">
        Submit
    </button>

    <div id="nff-message" style="margin-top:15px; font-weight:500;"></div>
</form>
