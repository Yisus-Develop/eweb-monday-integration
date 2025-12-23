<?php
// inspect-board-columns.php
// Script para inspeccionar las columnas y sus posibles valores en el tablero de Leads

require_once '../config.php';
require_once 'MondayAPI.php';

function inspectBoardColumns() {
    echo "========================================\n";
    echo "  INSPECCIÓN DE COLUMNAS DEL TABLERO    \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        
        // Query para obtener información detallada del tablero
        $query = '
        query {
            boards(ids: 18392144864) {
                name
                columns {
                    id
                    title
                    type
                    settings_str
                }
            }
        }';
        
        $response = $monday->query($query);
        $board = $response['boards'][0] ?? null;
        
        if (!$board) {
            echo "❌ No se pudo obtener información del tablero.\n";
            return;
        }
        
        echo "🔍 Tablero: {$board['name']} (ID: 18392144864)\n";
        echo "📊 Columnas encontradas: " . count($board['columns']) . "\n\n";
        
        foreach ($board['columns'] as $column) {
            echo "Columna: {$column['title']}\n";
            echo "  ID: {$column['id']}\n";
            echo "  Tipo: {$column['type']}\n";
            
            // Mostrar configuración si está disponible
            if (!empty($column['settings_str'])) {
                $settings = json_decode($column['settings_str'], true);
                if ($settings && isset($settings['labels']) && is_array($settings['labels'])) {
                    echo "  Etiquetas disponibles:\n";
                    foreach ($settings['labels'] as $key => $label) {
                        echo "    - $key: '$label'\n";
                    }
                }
            }
            echo "\n";
        }
        
        echo "========================================\n";
        echo "         INSPECCIÓN COMPLETADA          \n";
        echo "========================================\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

// Ejecutar la inspección
inspectBoardColumns();
?>
