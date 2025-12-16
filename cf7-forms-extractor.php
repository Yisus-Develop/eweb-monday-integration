<?php
/**
 * Plugin Name: CF7 Forms Extractor
 * Description: Extrae la estructura de todos los formularios Contact Form 7 para análisis
 * Version: 1.0
 * Author: AI Assistant
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Agregar página de admin
add_action('admin_menu', 'cf7_extractor_menu');

function cf7_extractor_menu() {
    add_management_page(
        'CF7 Forms Extractor',
        'CF7 Extractor',
        'manage_options',
        'cf7-extractor',
        'cf7_extractor_page'
    );
}

function cf7_extractor_page() {
    ?>
    <div class="wrap">
        <h1>Contact Form 7 - Extractor de Formularios</h1>
        <p>Este plugin extrae la estructura de todos tus formularios CF7.</p>
        
        <?php
        if (isset($_POST['extract_forms'])) {
            cf7_extract_and_display();
        } else {
            ?>
            <form method="post">
                <input type="submit" name="extract_forms" class="button button-primary" value="Extraer Formularios">
            </form>
            <?php
        }
        ?>
    </div>
    <?php
}

function cf7_extract_and_display() {
    // Obtener todos los formularios CF7
    $args = array(
        'post_type' => 'wpcf7_contact_form',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $forms = get_posts($args);
    
    if (empty($forms)) {
        echo '<div class="notice notice-error"><p>No se encontraron formularios CF7.</p></div>';
        return;
    }
    
    echo '<h2>Formularios Encontrados: ' . count($forms) . '</h2>';
    
    $formsData = [];
    
    foreach ($forms as $form) {
        $form_id = $form->ID;
        $form_title = $form->post_title;
        
        // Obtener el contenido del formulario
        $form_content = get_post_meta($form_id, '_form', true);
        
        echo '<div style="border: 1px solid #ccc; padding: 15px; margin: 15px 0; background: #f9f9f9;">';
        echo '<h3>' . esc_html($form_title) . ' (ID: ' . $form_id . ')</h3>';
        
        if (empty($form_content)) {
            echo '<p><em>Sin contenido</em></p>';
            echo '</div>';
            continue;
        }
        
        // Extraer campos
        preg_match_all('/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)(?:\s+([^\]]*))?\]/', $form_content, $matches, PREG_SET_ORDER);
        
        $fields = [];
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Opciones</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($matches as $match) {
            $type = str_replace('*', '', $match[1]);
            $fieldName = $match[2];
            $options = $match[3] ?? '';
            
            // Filtrar campos de sistema
            if (in_array($fieldName, ['_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag']) 
                || in_array($type, ['submit', 'acceptance'])) {
                continue;
            }
            
            echo '<tr>';
            echo '<td><code>' . esc_html($fieldName) . '</code></td>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '<td>' . esc_html($options) . '</td>';
            echo '</tr>';
            
            $fields[] = [
                'name' => $fieldName,
                'type' => $type,
                'options' => $options
            ];
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        $formsData[] = [
            'id' => $form_id,
            'title' => $form_title,
            'fields' => $fields
        ];
    }
    
    // Generar JSON descargable
    $json = json_encode($formsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    echo '<h2>Datos JSON (Copiar y Guardar)</h2>';
    echo '<textarea readonly style="width: 100%; height: 300px; font-family: monospace;">';
    echo esc_textarea($json);
    echo '</textarea>';
    
    echo '<p><strong>Instrucciones:</strong> Copia el contenido del textarea y guárdalo como <code>cf7_forms_analysis.json</code></p>';
}
