<?php
// workspace-audit-full.php
// Script para auditar completamente el workspace, mapear todos los tableros y probar operaciones

require_once '../config.php';
require_once 'MondayAPI.php';

function auditWorkspace() {
    echo "========================================\n";
    echo "  AUDITORÍA COMPLETA DEL WORKSPACE      \n";
    echo "  ID: 13608938                        \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $workspaceId = '13608938';
        
        // 1. Obtener todos los tableros en el workspace
        echo "🔍 OBTENIENDO TODOS LOS TABLEROS...\n";
        $query = '
        query {
            boards (workspace_ids: "'.$workspaceId.'") {
                id
                name
                state
                permissions
                owner {
                    id
                    name
                }
                subscribers {
                    id
                    name
                }
            }
        }';
        
        $response = $monday->query($query);
        $boards = $response['boards'] ?? [];
        
        echo "📊 TABLEROS ENCONTRADOS: " . count($boards) . "\n";
        
        $results = [
            'total_boards' => count($boards),
            'boards' => [],
            'operations_summary' => [
                'read_attempts' => 0,
                'read_success' => 0,
                'create_attempts' => 0,
                'create_success' => 0,
                'update_attempts' => 0,
                'update_success' => 0,
                'errors' => []
            ]
        ];
        
        foreach ($boards as $index => $board) {
            echo "\n" . ($index + 1) . ". TABLERO: {$board['name']} (ID: {$board['id']})\n";
            echo "   Estado: {$board['state']}\n";
            echo "   Permisos: " . json_encode($board['permissions']) . "\n";
            echo "   Propietario: {$board['owner']['name']} (ID: {$board['owner']['id']})\n";
            
            // 2. Obtener información detallada de las columnas
            echo "   🔍 Inspeccionando columnas...\n";
            $results['operations_summary']['read_attempts']++;
            
            $columnQuery = '
            query {
                boards(ids: '.$board['id'].') {
                    name
                    columns {
                        id
                        title
                        type
                        description
                        settings_str
                    }
                }
            }';
            
            try {
                $columnResponse = $monday->query($columnQuery);
                $boardDetails = $columnResponse['boards'][0];
                $columns = $boardDetails['columns'] ?? [];
                
                $results['operations_summary']['read_success']++;
                
                echo "   📊 Columnas encontradas: " . count($columns) . "\n";
                
                $columnInfo = [];
                foreach ($columns as $col) {
                    $columnInfo[] = [
                        'id' => $col['id'],
                        'title' => $col['title'],
                        'type' => $col['type']
                    ];
                    
                    // Mostrar configuración si está disponible
                    if (!empty($col['settings_str'])) {
                        $settings = json_decode($col['settings_str'], true);
                        if ($settings && isset($settings['labels']) && is_array($settings['labels'])) {
                            echo "      - {$col['title']} ({$col['id']}): Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
                        }
                    } else {
                        echo "      - {$col['title']} ({$col['id']}) - Tipo: {$col['type']}\n";
                    }
                }
                
                // 3. Probar operaciones CRUD en tableros donde sea posible
                echo "   🧪 Probando operaciones en este tablero...\n";
                
                // No crear en tableros de subitems
                if (strpos(strtolower($board['name']), 'subelementos') === false && 
                    strpos(strtolower($board['name']), 'subitems') === false) {
                    
                    // Probar crear un ítem de prueba
                    echo "     📥 Intentando crear ítem de prueba...\n";
                    $results['operations_summary']['create_attempts']++;
                    
                    $testItemName = 'Prueba API - ' . date('H:i:s');
                    
                    // Preparar valores para columnas comunes
                    $columnValues = [];
                    foreach ($columns as $col) {
                        if ($col['type'] === 'name') {
                            // Ya está en el nombre del ítem
                            continue;
                        } elseif ($col['type'] === 'text') {
                            $columnValues[$col['id']] = 'Dato de prueba';
                        } elseif ($col['type'] === 'email') {
                            $columnValues[$col['id']] = ['email' => 'test@example.com', 'text' => 'test@example.com'];
                        } elseif ($col['type'] === 'phone') {
                            $columnValues[$col['id']] = ['phone' => '123456789', 'country_short_name' => 'ES'];
                        } elseif ($col['type'] === 'status' || $col['type'] === 'dropdown') {
                            // Intentar con la primera opción disponible
                            if (!empty($col['settings_str'])) {
                                $settings = json_decode($col['settings_str'], true);
                                if ($settings && isset($settings['labels']) && is_array($settings['labels']) && count($settings['labels']) > 0) {
                                    $firstLabel = array_values($settings['labels'])[0];
                                    if ($firstLabel !== '') {
                                        $columnValues[$col['id']] = ['label' => $firstLabel];
                                    }
                                }
                            }
                        } elseif ($col['type'] === 'numbers') {
                            $columnValues[$col['id']] = 10;
                        }
                    }
                    
                    try {
                        $itemResponse = $monday->createItem($board['id'], $testItemName, $columnValues);
                        
                        if (isset($itemResponse['create_item']['id'])) {
                            $createdItemId = $itemResponse['create_item']['id'];
                            echo "     ✅ Ítem creado exitosamente (ID: $createdItemId)\n";
                            $results['operations_summary']['create_success']++;
                            
                            // Probar actualización
                            echo "     📝 Intentando actualizar ítem...\n";
                            $results['operations_summary']['update_attempts']++;
                            
                            $updateValues = $columnValues;
                            foreach ($updateValues as $key => $value) {
                                if (is_string($value) && $value === 'Dato de prueba') {
                                    $updateValues[$key] = 'Dato actualizado';
                                }
                            }
                            
                            $updateResponse = $monday->updateItem($board['id'], $createdItemId, $updateValues);
                            
                            if (isset($updateResponse['update_item']['id'])) {
                                echo "     ✅ Ítem actualizado exitosamente\n";
                                $results['operations_summary']['update_success']++;
                            } else {
                                echo "     ❌ Error al actualizar ítem: " . json_encode($updateResponse) . "\n";
                            }
                        } else {
                            echo "     ❌ Error al crear ítem: " . json_encode($itemResponse) . "\n";
                        }
                    } catch (Exception $e) {
                        echo "     ❌ Error al crear ítem: " . $e->getMessage() . "\n";
                        $results['operations_summary']['errors'][] = [
                            'board_id' => $board['id'],
                            'board_name' => $board['name'],
                            'operation' => 'create_item',
                            'error' => $e->getMessage()
                        ];
                    }
                } else {
                    echo "     ⚠️  Saltando operaciones CRUD (tablero de subelementos)\n";
                }
                
                $boardInfo = [
                    'id' => $board['id'],
                    'name' => $board['name'],
                    'state' => $board['state'],
                    'permissions' => $board['permissions'],
                    'columns' => $columnInfo,
                    'read_success' => true
                ];
                
            } catch (Exception $e) {
                echo "   ❌ Error al leer columnas: " . $e->getMessage() . "\n";
                $results['operations_summary']['errors'][] = [
                    'board_id' => $board['id'],
                    'board_name' => $board['name'],
                    'operation' => 'read_columns',
                    'error' => $e->getMessage()
                ];
                
                $boardInfo = [
                    'id' => $board['id'],
                    'name' => $board['name'],
                    'state' => $board['state'],
                    'read_success' => false,
                    'error' => $e->getMessage()
                ];
            }
            
            $results['boards'][] = $boardInfo;
        }
        
        // Mostrar resumen
        echo "\n========================================\n";
        echo "         RESUMEN DE LA AUDITORÍA        \n";
        echo "========================================\n";
        echo "Total de tableros: {$results['total_boards']}\n";
        echo "Intentos de lectura de columnas: {$results['operations_summary']['read_attempts']}\n";
        echo "Lecturas exitosas: {$results['operations_summary']['read_success']}\n";
        echo "Intentos de creación: {$results['operations_summary']['create_attempts']}\n";
        echo "Creaciones exitosas: {$results['operations_summary']['create_success']}\n";
        echo "Intentos de actualización: {$results['operations_summary']['update_attempts']}\n";
        echo "Actualizaciones exitosas: {$results['operations_summary']['update_success']}\n";
        echo "Errores totales: " . count($results['operations_summary']['errors']) . "\n";
        
        if (!empty($results['operations_summary']['errors'])) {
            echo "\nErrores encontrados:\n";
            foreach ($results['operations_summary']['errors'] as $error) {
                echo "  - Tablero {$error['board_name']} ({$error['board_id']}): {$error['error']} (Operación: {$error['operation']})\n";
            }
        }
        
        // Guardar resultados detallados
        file_put_contents('workspace_audit_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n📊 Resultados detallados guardados en: workspace_audit_results.json\n";
        
        echo "\n========================================\n";
        echo "      AUDITORÍA COMPLETA FINALIZADA      \n";
        echo "========================================\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL EN LA AUDITORÍA: " . $e->getMessage() . "\n";
    }
}

// Ejecutar la auditoría
auditWorkspace();
?>
