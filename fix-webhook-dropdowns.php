<?php
// fix-webhook-dropdowns.php
// Script para identificar y corregir el formato de las columnas dropdown

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  IDENTIFICACIÓN DE FORMATOS DROPDOWN    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Obtener información detallada de las columnas
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            columns {
                id
                title
                type
                settings_str
            }
        }
    }';
    
    $result = $monday->query($query);
    $columns = $result['boards'][0]['columns'] ?? [];
    
    // Buscar las columnas dropdown específicas que están causando problemas
    $problematicColumns = [
        'dropdown_mkyp8q98',  // Tipo de Lead
        'dropdown_mkypf16c',  // Canal de Origen
        'dropdown_mkyps472'   // Idioma
    ];
    
    echo "Columnas dropdown problemáticas:\n\n";
    
    foreach ($columns as $column) {
        if (in_array($column['id'], $problematicColumns)) {
            echo "ID: {$column['id']}\n";
            echo "Título: {$column['title']}\n";
            echo "Tipo: {$column['type']}\n";
            echo "Configuración: {$column['settings_str']}\n";
            
            $settings = json_decode($column['settings_str'], true);
            if (isset($settings['labels'])) {
                echo "Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
            }
            echo "\n";
        }
    }
    
    // Obtener también la configuración de las columnas de status para referencia
    echo "Columnas de status para referencia:\n\n";
    
    $statusColumns = [
        'color_mkypv3rg',  // Clasificación
        'color_mkyng649',  // Rol Detectado
        'lead_status'      // Estado
    ];
    
    foreach ($columns as $column) {
        if (in_array($column['id'], $statusColumns)) {
            echo "ID: {$column['id']}\n";
            echo "Título: {$column['title']}\n";
            echo "Tipo: {$column['type']}\n";
            echo "Configuración: {$column['settings_str']}\n";
            
            $settings = json_decode($column['settings_str'], true);
            if (isset($settings['labels'])) {
                echo "Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
            }
            echo "\n";
        }
    }
    
    echo "========================================\n";
    echo "INSTRUCCIONES PARA CORREGIR FORMATEO:\n";
    echo "========================================\n";
    echo "1. Para columnas dropdown, usar el formato correcto:\n";
    echo "   - Para 'Tipo de Lead': ['label' => 'Valor'] SI las opciones son simples\n";
    echo "   - Para 'Canal de Origen': ['label' => 'Valor'] SI las opciones son simples\n";
    echo "   - Para 'Idioma': ['label' => 'Valor'] SI las opciones son simples\n";
    echo "2. Si las opciones no existen, deben crearse manualmente primero\n";
    echo "3. La estructura debe coincidir exactamente con las opciones existentes\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>
