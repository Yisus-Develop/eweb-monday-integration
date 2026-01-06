<?php
/**
 * Plugin Name: CF7 Forms Extractor
 * Description: Extrae la estructura de todos los formularios Contact Form 7 para análisis
 * Version: 1.0
 * Author: AI Assistant
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// 1. Manejo de Descargas (Debe ir antes que cualquier salida)
add_action('admin_init', 'cf7_extractor_handle_download');
function cf7_extractor_handle_download() {
    if (isset($_GET['page']) && $_GET['page'] === 'cf7-extractor' && isset($_GET['download'])) {
        $format = $_GET['download'];
        $data = cf7_get_all_forms_data();
        
        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=cf7_forms_mapping.json');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        } elseif ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=cf7_forms_mapping.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Form ID', 'Form Title', 'Field Name', 'Type', 'Mapping Status']);
            foreach ($data as $form) {
                foreach ($form['fields'] as $field) {
                    fputcsv($output, [
                        $form['id'], 
                        $form['title'], 
                        $field['name'], 
                        $field['type'], 
                        $field['mapped'] ? 'MAPPED' : 'NEW/UNMAPPED'
                    ]);
                }
            }
            fclose($output);
            exit;
        }
    }
}

// 2. Agregar página de admin
add_action('admin_menu', 'cf7_extractor_menu');
function cf7_extractor_menu() {
    add_management_page('CF7 Extractor', 'CF7 Extractor', 'manage_options', 'cf7-extractor', 'cf7_extractor_page');
}

function cf7_extractor_page() {
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center;">
            <span class="dashicons dashicons-media-spreadsheet" style="margin-right: 10px; font-size: 32px; width: 32px; height: 32px;"></span>
            CF7 Forms Extractor & Mapping Validator
        </h1>
        <p>Analiza y exporta la estructura de tus formularios para la integración con Monday CRM.</p>
        
        <div class="action-bar" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
            <form method="post" style="display: inline-block;">
                <input type="submit" name="extract_forms" class="button button-primary button-large" value="🚀 Escanear y Validar Mapeos">
            </form>
            
            <?php if (isset($_POST['extract_forms'])): ?>
                <a href="<?php echo admin_url('admin.php?page=cf7-extractor&download=json'); ?>" class="button button-secondary button-large">📥 Descargar JSON</a>
                <a href="<?php echo admin_url('admin.php?page=cf7-extractor&download=csv'); ?>" class="button button-secondary button-large">📊 Descargar CSV</a>
            <?php endif; ?>
        </div>

        <?php
        if (isset($_POST['extract_forms'])) {
            cf7_extract_and_display();
        }
        ?>
    </div>
    <?php
}

function cf7_get_all_forms_data() {
    $forms = get_posts(['post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1, 'post_status' => 'publish']);
    $formsData = [];
    
    // Lista de campos que ya manejamos en webhook-handler.php
    $recognizedFields = [
        'nombre', 'nombre_empresa', 'your-name', 'contact_name', 'ea_firstname', 'first-name', 'full-name', 'first', 'last', 'name', 'ea_lastname',
        'email', 'your-email', 'ea_email', 
        'telefono', 'your-phone', 'tel-641', 'phone', 
        'pais_cf7', 'pais_otro', 'ea_country', 'country',
        'ciudad_cf7', 'ea_city', 'city',
        'perfil', 'profile', 'tipo_institucion', 'numero_estudiantes', 'poblacion', 'population', 'modality', 'org_name', 'company', 'entity', 'institucion', 'institution', 'ea_institution',
        'asunto', 'mensaje', 'message', 'aliados_potenciales', 'interes', 'sector', 'especialidad', 'specialty', 'speciality', 'ea_role', 'fecha_nacimiento'
    ];

    foreach ($forms as $form) {
        $content = get_post_meta($form->ID, '_form', true);
        preg_match_all('/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)(?:\s+([^\]]*))?\]/', $content, $matches, PREG_SET_ORDER);
        
        $fields = [];
        foreach ($matches as $match) {
            $type = str_replace('*', '', $match[1]);
            $name = $match[2];
            if (in_array($name, ['_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag']) || in_array($type, ['submit', 'acceptance'])) continue;
            
            $fields[] = [
                'name' => $name,
                'type' => $type,
                'mapped' => in_array($name, $recognizedFields)
            ];
        }
        
        $formsData[] = ['id' => $form->ID, 'title' => $form->post_title, 'fields' => $fields];
    }
    return $formsData;
}

function cf7_extract_and_display() {
    $data = cf7_get_all_forms_data();
    echo '<h2>Resultados del Escaneo (' . count($data) . ' formularios)</h2>';
    
    foreach ($data as $form) {
        echo '<div class="postbox" style="margin-bottom: 20px;">';
        echo '<div class="postbox-header"><h2 class="hndle" style="padding: 10px;">' . esc_html($form['title']) . ' <small>(ID: ' . $form['id'] . ')</small></h2></div>';
        echo '<div class="inside">';
        
        if (empty($form['fields'])) {
            echo '<p>Este formulario no tiene campos de datos.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID del Campo</th><th>Tipo</th><th>Estado en Monday Integration</th></tr></thead>';
            echo '<tbody>';
            foreach ($form['fields'] as $f) {
                $statusColor = $f['mapped'] ? '#46b450' : '#ffa500';
                $statusText = $f['mapped'] ? '✅ MAPEADO' : '⚠️ NUEVO / SIN MAPEO';
                echo "<tr>
                    <td><code>{$f['name']}</code></td>
                    <td>{$f['type']}</td>
                    <td style='color: $statusColor; font-weight: bold;'>$statusText</td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div></div>';
    }
}
