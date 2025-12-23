<?php
/**
 * Plugin Name:       Monday.com CF7 Webhook Trigger
 * Description:       Fires a webhook to the Monday.com integration handler after a Contact Form 7 submission.
 * Version:           1.0
 * Author:            Yisus Develop
 * Author URI:        https://enlaweb.co/
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       Monday.com CF7 Webhook Trigger
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.4
 * Requires PHP:      8.1
 */


if (!defined('WPINC')) {
    die;
}

add_action('wpcf7_mail_sent', 'send_to_monday_webhook');

function send_to_monday_webhook($contact_form) {
    // Get the submission data
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }
    $posted_data = $submission->get_posted_data();

    // Exclude specific forms if needed (e.g., newsletter signup)
    // The roadmap mentions excluding form 5410, so we will add that logic.
    if ($contact_form->id() == 5410) {
        return;
    }

    // Construct the webhook URL dynamically.
    // This assumes the 'monday-integration' folder is in wp-content.
    // A more robust solution might use a settings page, but this follows the roadmap's example.
    $webhook_url = content_url('/monday-integration/webhook-handler.php');

    // Send the data to the webhook handler
    wp_remote_post($webhook_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($posted_data),
        'timeout' => 15,
        'blocking' => false, // Set to false to not wait for the response
    ]);
}
