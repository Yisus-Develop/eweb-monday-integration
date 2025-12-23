<?php
/**
 * Plugin Name:       Monday.com Lead Monitor & Trigger
 * Description:       Fires a webhook to Monday.com and provides an admin dashboard to track leads.
 * Version:           2.0
 * Author:            Yisus Develop
 * Requires PHP:      8.1
 */

if (!defined('WPINC')) {
    die;
}

// 1. Capture Hook from CF7
add_action('wpcf7_mail_sent', 'monday_trigger_webhook_on_sent');

// 2. Database Setup on Activation
register_activation_hook(__FILE__, 'monday_integration_create_db');
function monday_integration_create_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        source varchar(100) DEFAULT '' NOT NULL,
        status varchar(50) DEFAULT '' NOT NULL,
        response_body text NOT NULL,
        full_payload longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function monday_trigger_webhook_on_sent($contact_form) {
    if ($contact_form->id() == 5410) return; // Excluir suscripción

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    monday_send_to_handler($data, "Form: " . $contact_form->title());
}

// 3. Logic to Send Webhook
function monday_send_to_handler($data, $source = "Manual Test") {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';
    $webhook_url = content_url('/monday-integration/webhook-handler.php');
    
    $response = wp_remote_post($webhook_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($data),
        'timeout' => 20,
        'blocking' => true,
    ]);

    // Registro en la Base de Datos SQL
    $wpdb->insert($table_name, [
        'time'          => current_time('mysql'),
        'email'         => $data['email'] ?? $data['your-email'] ?? 'N/A',
        'source'        => $source,
        'status'        => is_wp_error($response) ? 'Error' : wp_remote_retrieve_response_code($response),
        'response_body' => is_wp_error($response) ? $response->get_error_message() : substr(wp_remote_retrieve_body($response), 0, 500),
        'full_payload'  => json_encode($data)
    ]);
    
    return $response;
}

// 4. Admin Menu & Dashboard
add_action('admin_menu', function() {
    add_menu_page('Monday Leads', 'Monday Leads', 'manage_options', 'monday-monitor', 'monday_monitor_page_html', 'dashicons-chart-line');
});

function monday_monitor_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';

    if (isset($_POST['monday_test_trigger'])) {
        monday_send_to_handler(['nombre' => 'Test DB', 'email' => 'test_db@enlaweb.co', 'pais_cf7' => 'España', 'perfil' => 'empresa'], "Dashboard Test");
        echo '<div class="updated"><p>¡Test lanzado y guardado en DB!</p></div>';
    }

    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 50");
    ?>
    <div class="wrap">
        <h1>📊 Monitor de Integración Monday.com</h1>
        <form method="post"><input type="submit" name="monday_test_trigger" class="button button-primary" value="Enviar Lead de Prueba"></form>
        
        <h2>Últimos 50 Envíos (Base de Datos)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Email</th>
                    <th>Origen</th>
                    <th>Status</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log->time; ?></td>
                    <td><?php echo $log->email; ?></td>
                    <td><?php echo $log->source; ?></td>
                    <td><span style="color: <?php echo $log->status == 200 ? 'green' : 'red'; ?>"><?php echo $log->status; ?></span></td>
                    <td>
                        <button type="button" class="button button-small" onclick="alert(this.nextElementSibling.innerText)">Ver JSON</button>
                        <div style="display:none"><?php echo esc_html($log->full_payload); ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
