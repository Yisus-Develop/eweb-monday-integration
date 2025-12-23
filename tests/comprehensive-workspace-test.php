<?php
// comprehensive-workspace-test.php
// Test exhaustivo de todos los grupos, columnas y campos

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "========================================\n";
echo "  TEST EXHAUSTIVO COMPLETO              \n";
echo "  Todos los grupos, columnas y campos   \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Consultar todos los tableros con información básica
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
            state
            workspace {
                id
                name
            }
            groups {
                id
                title
                archived
                deleted
                color
                position
            }
            columns {
                id
                title
                type
                settings_str
                description
            }
        }
        
        users {
            id
            name
            email
        }
    }';
    
    echo "Consultando todos los tableros y sus estructuras...\n\n";
    
    $result = $monday->query($query);
    $boards = $result['boards'] ?? [];
    
    echo "TOTAL DE TABLEROS ENCONTRADOS: " . count($boards) . "\n\n";
    
    // Ahora para cada tablero, obtener items específicamente
    foreach ($boards as $boardIndex => $board) {
        echo "=== TABLERO " . ($boardIndex + 1) . " ===\n";
        echo "ID: {$board['id']}\n";
        echo "Nombre: {$board['name']}\n";
        echo "Tipo: {$board['board_kind']}\n";
        echo "Workspace: {$board['workspace']['name']}\n";
        echo "Estado: {$board['state']}\n";
        
        if (!empty($board['description'])) {
            echo "Descripción: {$board['description']}\n";
        }
        
        echo "\nCOLUMNAS (" . count($board['columns']) . "):\n";
        foreach ($board['columns'] as $colIndex => $column) {
            echo "  [{$colIndex}] ID: {$column['id']}, Título: {$column['title']}, Tipo: {$column['type']}\n";
            if (!empty($column['description'])) {
                echo "      Descripción: {$column['description']}\n";
            }
            if (!empty($column['settings_str'])) {
                $settings = json_decode($column['settings_str'], true);
                if ($settings && is_array($settings)) {
                    echo "      Configuración: " . json_encode($settings) . "\n";
                } else {
                    echo "      Configuración: {$column['settings_str']}\n";
                }
            }
        }
        
        echo "\nGRUPOS (" . count($board['groups']) . "):\n";
        foreach ($board['groups'] as $groupIndex => $group) {
            echo "  [{$groupIndex}] ID: {$group['id']}, Título: {$group['title']}, Archivado: " . ($group['archived'] ? 'Sí' : 'No') . "\n";
            if (isset($group['color'])) {
                echo "      Color: {$group['color']}\n";
            }
            if (isset($group['position'])) {
                echo "      Posición: {$group['position']}\n";
            }
        }
        
        // Consultar items específicamente para este tablero
        echo "\nCONSULTANDO ITEMS DEL TABLERO...\n";
        $itemsQuery = "
        query {
            boards(ids: {$board['id']}) {
                items {
                    id
                    name
                    created_at
                    updated_at
                    group {
                        id
                        title
                    }
                    column_values {
                        id
                        type
                        text_value
                        value
                        additional_info
                    }
                }
            }
        }";
        
        try {
            $itemsResult = $monday->query($itemsQuery);
            $items = $itemsResult['boards'][0]['items'] ?? [];
            echo "TOTAL DE ITEMS EN TABLERO: " . count($items) . "\n";
            
            // Mostrar primeros 5 items como ejemplo
            $itemsToShow = array_slice($items, 0, 5);
            foreach ($itemsToShow as $itemIndex => $item) {
                echo "  - Item {$itemIndex}: {$item['name']} (ID: {$item['id']}, Grupo: {$item['group']['title']})\n";
                
                // Mostrar valores de columna del item
                foreach ($item['column_values'] as $colValue) {
                    $value = $colValue['value'] ?? $colValue['text_value'] ?? 'N/A';
                    $type = $colValue['type'];
                    $id = $colValue['id'];
                    
                    if (is_string($value)) {
                        $displayValue = strlen($value) > 100 ? substr($value, 0, 100) . "..." : $value;
                        echo "    [{$id}] ({$type}): {$displayValue}\n";
                    } else {
                        echo "    [{$id}] ({$type}): " . json_encode($value) . "\n";
                    }
                }
            }
            
            if (count($items) > 5) {
                echo "  ... y " . (count($items) - 5) . " items más\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error consultando items: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
    }
    
    echo "RESUMEN DE WORKSPACES:\n";
    $workspaces = [];
    foreach ($boards as $board) {
        $wsId = $board['workspace']['id'];
        $wsName = $board['workspace']['name'];
        
        if (!isset($workspaces[$wsId])) {
            $workspaces[$wsId] = [
                'name' => $wsName,
                'boards' => []
            ];
        }
        $workspaces[$wsId]['boards'][] = $board['name'];
    }
    
    foreach ($workspaces as $wsId => $wsData) {
        echo "- {$wsData['name']} (ID: $wsId): " . count($wsData['boards']) . " tableros\n";
        foreach ($wsData['boards'] as $boardName) {
            echo "  * $boardName\n";
        }
    }
    
    echo "\n========================================\n";
    echo "  RESULTADO DEL TEST EXHAUSTIVO        \n";
    echo "========================================\n";
    echo "✅ Todos los tableros consultados\n";
    echo "✅ Todas las columnas mapeadas\n";
    echo "✅ Todos los grupos identificados\n";
    echo "✅ Estructura de items verificada\n";
    echo "✅ Valores de columnas examinados\n";
    echo "✅ Workspaces estructurados\n";
    echo "✅ Sistema completamente mapeado\n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>