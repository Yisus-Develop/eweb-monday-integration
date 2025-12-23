<?php
// verify-column-creation.php
// Script para verificar cómo se crearon las nuevas columnas

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  VERIFICACIÓN DE COLUMNAS CREADAS      \n";
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
    
    echo "Columnas encontradas: " . count($columns) . "\n\n";
    
    // Buscar nuestras nuevas columnas
    $newColumns = [
        'classification_status',  // Clasificación
        'role_detected_new',      // Rol Detectado
        'type_of_lead',           // Tipo de Lead
        'source_channel',         // Canal de Origen
        'language'               // Idioma
    ];
    
    foreach ($columns as $column) {
        if (in_array($column['id'], $newColumns)) {
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
    echo "INSTRUCCIONES PARA FORMATO CORRECTO:    \n";
    echo "========================================\n";
    echo "Para columnas STATUS, usar: ['label' => 'Nombre de la opción']\n";
    echo "Para columnas DROPDOWN, usar: ['label' => 'Nombre de la opción']\n";
    echo "Pero si los valores no coinciden exactamente con las opciones,\n";
    echo "la API fallará. Verifica que los valores estén exactamente como\n";
    echo "se definieron al crear las columnas.\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>
