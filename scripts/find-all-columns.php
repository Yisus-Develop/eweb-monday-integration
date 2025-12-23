<?php
// find-all-columns.php
// Script para encontrar todas las columnas y sus tipos

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  LISTADO COMPLETO DE COLUMNAS          \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Obtener todas las columnas
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
    
    echo "Total de columnas: " . count($columns) . "\n\n";
    
    foreach ($columns as $column) {
        echo "ID: {$column['id']}\n";
        echo "Título: {$column['title']}\n";
        echo "Tipo: {$column['type']}\n";
        echo "Configuración: {$column['settings_str']}\n";
        
        // Buscar columnas específicas
        if (strpos(strtolower($column['title']), 'cronograma') !== false) {
            echo "🔍 ¡ENCONTRADA COLUMNA CRONOGRAMA!\n";
        }
        if (strpos(strtolower($column['title']), 'actividades') !== false) {
            echo "🔍 ¡ENCONTRADA COLUMNA ACTIVIDADES!\n";
        }
        
        echo "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>
