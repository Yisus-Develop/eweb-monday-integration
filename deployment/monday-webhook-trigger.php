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

    // Verificar que la tabla existe (por si no se activó el plugin correctamente)
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Crear tabla si no existe
        monday_integration_create_db();
    }

    // Registro en la Base de Datos SQL
    $insert_result = $wpdb->insert($table_name, [
        'time'          => current_time('mysql'),
        'email'         => $data['email'] ?? $data['your-email'] ?? 'N/A',
        'source'        => $source,
        'status'        => is_wp_error($response) ? 'Error' : wp_remote_retrieve_response_code($response),
        'response_body' => is_wp_error($response) ? $response->get_error_message() : substr(wp_remote_retrieve_body($response), 0, 500),
        'full_payload'  => json_encode($data)
    ]);
    
    // Log de debug si falla el insert
    if ($insert_result === false) {
        error_log('Monday Integration: Failed to insert log. Error: ' . $wpdb->last_error);
    }
    
    return $response;
}

// 4. Admin Menu & Dashboard
add_action('admin_menu', function() {
    add_menu_page('Monday Leads', 'Monday Leads', 'manage_options', 'monday-monitor', 'monday_monitor_page_html', 'dashicons-chart-line');
});

function monday_monitor_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';

    // Test trigger
    if (isset($_POST['monday_test_trigger'])) {
        monday_send_to_handler([
            'nombre' => 'Test DB ' . date('H:i:s'), 
            'email' => 'test_db@enlaweb.co', 
            'pais_cf7' => 'España', 
            'perfil' => 'empresa'
        ], "Dashboard Test");
        echo '<div class="updated"><p>¡Test lanzado y guardado en DB!</p></div>';
    }

    // Paginación
    $per_page = 50;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Búsqueda
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where = $search ? $wpdb->prepare("WHERE email LIKE %s OR source LIKE %s", "%$search%", "%$search%") : '';
    
    // Total de registros
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
    $total_pages = ceil($total_items / $per_page);
    
    // Obtener registros
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name $where ORDER BY id DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    ?>
    <div class="wrap">
        <h1>📊 Monitor de Integración Monday.com</h1>
        
        <div style="display: flex; justify-content: space-between; margin: 20px 0;">
            <form method="post">
                <input type="submit" name="monday_test_trigger" class="button button-primary" value="Enviar Lead de Prueba">
            </form>
            
            <form method="get" style="display: flex; gap: 10px;">
                <input type="hidden" name="page" value="monday-monitor">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por email u origen...">
                <input type="submit" class="button" value="Buscar">
                <?php if ($search): ?>
                    <a href="?page=monday-monitor" class="button">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num"><?php echo number_format_i18n($total_items); ?> registros</span>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php if ($current_page > 1): ?>
                        <a class="first-page button" href="?page=monday-monitor&paged=1<?php echo $search ? '&s=' . urlencode($search) : ''; ?>">«</a>
                        <a class="prev-page button" href="?page=monday-monitor&paged=<?php echo $current_page - 1; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">‹</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a class="next-page button" href="?page=monday-monitor&paged=<?php echo $current_page + 1; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">›</a>
                        <a class="last-page button" href="?page=monday-monitor&paged=<?php echo $total_pages; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">»</a>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 150px;">Fecha</th>
                    <th>Email</th>
                    <th style="width: 150px;">Origen</th>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 100px;">Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <?php echo $search ? 'No se encontraron resultados.' : 'No hay registros aún. Envía un lead de prueba.'; ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->time); ?></td>
                        <td><strong><?php echo esc_html($log->email); ?></strong></td>
                        <td><?php echo esc_html($log->source); ?></td>
                        <td>
                            <span style="color: <?php echo $log->status == 200 ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo esc_html($log->status); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="
                                var modal = document.getElementById('json-modal-<?php echo $log->id; ?>');
                                modal.style.display = 'block';
                            ">Ver JSON</button>
                            
                            <!-- Modal -->
                            <div id="json-modal-<?php echo $log->id; ?>" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
                                <div style="background-color:#fff; margin:5% auto; padding:20px; width:80%; max-width:800px; border-radius:5px; max-height:80vh; overflow:auto;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                                        <h3>Payload Completo</h3>
                                        <button onclick="document.getElementById('json-modal-<?php echo $log->id; ?>').style.display='none'" style="cursor:pointer; font-size:20px; border:none; background:none;">&times;</button>
                                    </div>
                                    <pre style="background:#f5f5f5; padding:15px; border-radius:3px; overflow:auto;"><?php echo esc_html(json_encode(json_decode($log->full_payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php if ($current_page > 1): ?>
                        <a class="first-page button" href="?page=monday-monitor&paged=1<?php echo $search ? '&s=' . urlencode($search) : ''; ?>">«</a>
                        <a class="prev-page button" href="?page=monday-monitor&paged=<?php echo $current_page - 1; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">‹</a>
                    <?php endif; ?>
                    
                    <span class="paging-input">
                        Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a class="next-page button" href="?page=monday-monitor&paged=<?php echo $current_page + 1; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">›</a>
                        <a class="last-page button" href="?page=monday-monitor&paged=<?php echo $total_pages; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>">»</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

