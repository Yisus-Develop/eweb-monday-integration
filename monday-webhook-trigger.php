<?php
/**
 * Plugin Name:       Monday.com Lead Monitor & Trigger
 * Description:       Fires a webhook to Monday.com and provides an admin dashboard to track leads.
 * Version:           2.1
 * Author:            Yisus Develop
 * Requires PHP:      8.1
 */

if (!defined('WPINC')) die;

// 1. GitHub Auto-Updater
require_once plugin_dir_path(__FILE__) . 'includes/class-eweb-github-updater.php';
new EWEB_GitHub_Updater(__FILE__, 'Yisus-Develop', 'AI-Vault');

// 2. Core Dependencies
require_once plugin_dir_path(__FILE__) . 'includes/MondayAPI.php';
require_once plugin_dir_path(__FILE__) . 'includes/LeadScoring.php';
require_once plugin_dir_path(__FILE__) . 'includes/NewColumnIds.php';
require_once plugin_dir_path(__FILE__) . 'includes/StatusConstants.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-monday-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/db-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-dashboard.php';

// 3. Hooks
add_action('wpcf7_mail_sent', 'monday_trigger_webhook_on_sent');
register_activation_hook(__FILE__, 'monday_integration_create_db');

add_action('admin_menu', function() {
    add_menu_page('Monday Leads', 'Monday Leads', 'manage_options', 'monday-monitor', 'monday_monitor_page_html', 'dashicons-chart-line');
});

/**
 * Capture CF7 and trigger internal handler
 */
function monday_trigger_webhook_on_sent($contact_form) {
    if (!isset($contact_form)) return;
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    monday_send_to_handler($data, "Form: " . $contact_form->title());
}

/**
 * Glue logic between CF7 and Monday Handler
 */
function monday_send_to_handler($data, $source = "Manual Test") {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';
    
    // Process lead internally
    $result = Monday_Handler::process($data);
    
    // Ensure table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        monday_integration_create_db();
    }

    // Log to local DB
    $wpdb->insert($table_name, [
        'time'          => current_time('mysql'),
        'email'         => $data['email'] ?? $data['your-email'] ?? $data['ea_email'] ?? 'N/A',
        'source'        => $source,
        'status'        => $result['status'],
        'response_body' => substr($result['message'] ?? 'OK', 0, 500),
        'full_payload'  => json_encode($data)
    ]);
    
    return $result;
}
