<?php
// includes/admin-dashboard.php

if (!defined('WPINC')) die;

function monday_monitor_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';

    // 1. Manejo de Acciones (Re-enviar, Eliminar, Bulk, Settings)
    if (isset($_POST['monday_resend_log']) && isset($_POST['log_id'])) {
        check_admin_referer('monday_resend_log_' . $_POST['log_id']);
        $log_id = intval($_POST['log_id']);
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));
        if ($log) {
            $payload = json_decode($log->full_payload, true);
            $result = monday_send_to_handler($payload, "Re-envío Manual (ID: $log_id)");
            $wpdb->update($table_name, ['status' => $result['status'], 'response_body' => substr($result['message'] ?? 'OK', 0, 500), 'time' => current_time('mysql')], ['id' => $log_id]);
            echo '<div class="updated"><p>🚀 Re-envío completado. Status: <strong>' . $result['status'] . '</strong></p></div>';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && !empty($_POST['log_ids'])) {
        check_admin_referer('bulk-logs');
        $log_ids = array_map('intval', $_POST['log_ids']);
        $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $log_ids));
        echo '<div class="updated"><p>✅ ' . $deleted . ' registro(s) eliminado(s).</p></div>';
    }

    if (isset($_POST['monday_delete_log']) && isset($_POST['log_id'])) {
        check_admin_referer('monday_delete_log_' . $_POST['log_id']);
        $wpdb->delete($table_name, ['id' => intval($_POST['log_id'])]);
        echo '<div class="updated"><p>✅ Registro eliminado.</p></div>';
    }

    if (isset($_POST['monday_test_trigger'])) {
        monday_send_to_handler(['nombre' => 'Test Dashboard ' . date('H:i:s'), 'email' => 'test@enlaweb.co', 'pais_cf7' => 'España', 'perfil' => 'empresa'], "UI Test");
        echo '<div class="updated"><p>¡Test lanzado!</p></div>';
    }

    if (isset($_POST['monday_save_settings'])) {
        check_admin_referer('monday_save_settings');
        update_option('monday_api_token', sanitize_text_field($_POST['monday_api_token']));
        update_option('monday_board_id', sanitize_text_field($_POST['monday_board_id']));
        update_option('monday_debug_mode', isset($_POST['monday_debug_mode']) ? 'yes' : 'no');
        echo '<div class="updated"><p>✅ Configuración guardada.</p></div>';
    }

    // 2. Datos para la tabla
    $per_page = 50;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where = $search ? $wpdb->prepare("WHERE email LIKE %s OR source LIKE %s", "%$search%", "%$search%") : '';
    
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
    $total_pages = ceil($total_items / $per_page);
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'logs';
    $api_token = get_option('monday_api_token', '');
    $board_id = get_option('monday_board_id', '');
    $debug_mode = get_option('monday_debug_mode', 'yes');
    ?>

    <div class="wrap">
        <h1>📊 Monitor de Integración Monday.com</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=monday-monitor&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs de Leads</a>
            <a href="?page=monday-monitor&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">⚙️ Configuración</a>
        </h2>

        <?php if ($active_tab == 'settings'): ?>
            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-top:20px; max-width:600px;">
                <h3>Credenciales de Monday.com</h3>
                <form method="post">
                    <?php wp_nonce_field('monday_save_settings'); ?>
                    <table class="form-table">
                        <tr><th>API Token</th><td><input name="monday_api_token" type="password" value="<?php echo esc_attr($api_token); ?>" class="regular-text"></td></tr>
                        <tr><th>Board ID</th><td><input name="monday_board_id" type="text" value="<?php echo esc_attr($board_id); ?>" class="regular-text"></td></tr>
                        <tr><th>Modo Debug</th><td><label><input type="checkbox" name="monday_debug_mode" <?php checked($debug_mode, 'yes'); ?>> Logs activos</label></td></tr>
                    </table>
                    <?php submit_button('Guardar Cambios'); ?>
                </form>
            </div>
        <?php else: ?>
            <div style="display:flex; justify-content:space-between; margin:20px 0;">
                <form method="post"><input type="submit" name="monday_test_trigger" class="button button-primary" value="Enviar Lead de Prueba"></form>
                <form method="get">
                    <input type="hidden" name="page" value="monday-monitor">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar...">
                    <input type="submit" class="button" value="Buscar">
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th class="check-column"><input type="checkbox"></th><th>Fecha</th><th>Email</th><th>Origen</th><th>Status</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <th class="check-column"><input type="checkbox" name="log_ids[]" value="<?php echo $log->id; ?>" form="bulk-action-form"></th>
                        <td><?php echo $log->time; ?></td>
                        <td><strong><?php echo $log->email; ?></strong></td>
                        <td><?php echo $log->source; ?></td>
                        <td><span style="color:<?php echo $log->status == 200 ? 'green':'red'; ?>; font-weight:bold;"><?php echo $log->status; ?></span></td>
                        <td>
                            <form method="post" style="display:inline;"><?php wp_nonce_field('monday_resend_log_'.$log->id); ?><input type="hidden" name="log_id" value="<?php echo $log->id; ?>"><button type="submit" name="monday_resend_log" class="button button-small" title="Re-enviar">🔄</button></form>
                            <button type="button" class="button button-small" onclick="document.getElementById('modal-<?php echo $log->id; ?>').style.display='block'">JSON</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?')"><?php wp_nonce_field('monday_delete_log_'.$log->id); ?><input type="hidden" name="log_id" value="<?php echo $log->id; ?>"><button type="submit" name="monday_delete_log" class="button button-small" style="color:red;">🗑️</button></form>
                            
                            <!-- Modal JSON -->
                            <div id="modal-<?php echo $log->id; ?>" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                                <div style="background:#fff; margin:5% auto; padding:20px; width:80%; max-width:700px; border-radius:5px; max-height:80vh; overflow:auto;">
                                    <h3 style="display:flex; justify-content:space-between;">Payload <button onclick="document.getElementById('modal-<?php echo $log->id; ?>').style.display='none'" style="border:none; cursor:pointer;">&times;</button></h3>
                                    <pre style="background:#f5f5f5; padding:10px;"><?php echo esc_html(json_encode(json_decode($log->full_payload), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
