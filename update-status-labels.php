<?php
// update-status-labels.php
// Script para actualizar las etiquetas de status en Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';

function getBoardRevision($monday, $boardId) {
    // Consultar la revisión del tablero
    $query = '
    query {
        boards(ids: '.$boardId.') {
            id
            name
            updated_at
        }
    }';
    
    $result = $monday->query($query);
    $board = $result['boards'][0] ?? null;
    
    if ($board) {
        // Para la mutación, necesitamos una revisión. En algunos casos, 
        // podemos usar el hash del board o un valor derivado del updated_at
        // pero si no funciona, podemos intentar omitirlo o usar el ID
        return md5($boardId . $board['updated_at']);
    }
    
    return null;
}

function updateStatusLabels() {
    echo "========================================\n";
    echo "  ACTUALIZACIÓN DE ETIQUETAS DE STATUS   \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        // Obtener la revisión del tablero (aunque puede no ser necesaria en todas las versiones)
        echo "1. OBTENIENDO INFORMACIÓN DEL TABLERO...\n";
        
        $query = '
        query {
            boards(ids: '.$leadsBoardId.') {
                id
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
        $board = $result['boards'][0];
        $columns = $board['columns'] ?? [];
        
        // Buscar las columnas de status específicas
        $classificationColumn = null;
        $roleColumn = null;
        
        foreach ($columns as $column) {
            if ($column['id'] === 'color_mkypv3rg' && $column['title'] === 'Clasificación') {
                $classificationColumn = $column;
            } elseif ($column['id'] === 'color_mkyng649' && $column['title'] === 'Rol Detectado') {
                $roleColumn = $column;
            }
        }
        
        if (!$classificationColumn) {
            echo "❌ No se encontró la columna de clasificación\n";
            return false;
        }
        
        if (!$roleColumn) {
            echo "❌ No se encontró la columna de rol detectado\n";
            return false;
        }
        
        echo "   Columnas encontradas, preparando actualización...\n\n";
        
        // Actualizar columna de clasificación con colores válidos
        // Basado en los colores sugeridos por el error, usaremos los que Monday acepta
        $classificationUpdate = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkypv3rg",
            settings: {
              labels: [
                { label: "HOT", color: "dark_red", index: 0 },
                { label: "WARM", color: "working_orange", index: 1 },
                { label: "COLD", color: "dark_blue", index: 2 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "2. ACTUALIZANDO ETIQUETAS DE CLASIFICACIÓN...\n";
        echo "   Enviando actualización...\n";
        $result = $monday->rawQuery($classificationUpdate);
        
        if (isset($result['data']) && isset($result['data']['update_status_column'])) {
            echo "   ✅ Clasificación actualizada a HOT/WARM/COLD\n";
        } else {
            echo "   ❌ Error en actualización de clasificación: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            
            // Intentar con la mutación que solo actualiza las etiquetas sin color
            $classificationUpdateSimple = '
            mutation {
              update_status_column(
                board_id: '.$leadsBoardId.',
                id: "color_mkypv3rg",
                settings: {
                  labels: [
                    { label: "HOT", index: 0 },
                    { label: "WARM", index: 1 },
                    { label: "COLD", index: 2 }
                  ]
                }
              ) {
                id
              }
            }';
            
            echo "   Reintentando sin colores específicos...\n";
            $result = $monday->rawQuery($classificationUpdateSimple);
            
            if (isset($result['data']) && isset($result['data']['update_status_column'])) {
                echo "   ✅ Clasificación actualizada sin colores\n";
            } else {
                echo "   ❌ Error también sin colores: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
                echo "   ⚠️  Esta actualización puede requerir hacerse manualmente en la interfaz de Monday\n";
            }
        }
        
        // Actualizar columna de rol detectado
        $roleUpdate = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkyng649",
            settings: {
              labels: [
                { label: "Mission Partner", color: "dark_purple", index: 0 },
                { label: "Rector/Director", color: "dark_green", index: 1 },
                { label: "Alcalde/Gobierno", color: "dark_orange", index: 2 },
                { label: "Corporate", color: "dark_sky", index: 3 },
                { label: "Maestro/Mentor", color: "dark_pink", index: 4 },
                { label: "Joven", color: "grass_green", index: 5 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "\n3. ACTUALIZANDO ETIQUETAS DE ROL DETECTADO...\n";
        echo "   Enviando actualización...\n";
        $result = $monday->rawQuery($roleUpdate);
        
        if (isset($result['data']) && isset($result['data']['update_status_column'])) {
            echo "   ✅ Roles actualizados correctamente\n";
        } else {
            echo "   ❌ Error en actualización de roles: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            
            // Intentar con la mutación simple
            $roleUpdateSimple = '
            mutation {
              update_status_column(
                board_id: '.$leadsBoardId.',
                id: "color_mkyng649",
                settings: {
                  labels: [
                    { label: "Mission Partner", index: 0 },
                    { label: "Rector/Director", index: 1 },
                    { label: "Alcalde/Gobierno", index: 2 },
                    { label: "Corporate", index: 3 },
                    { label: "Maestro/Mentor", index: 4 },
                    { label: "Joven", index: 5 }
                  ]
                }
              ) {
                id
              }
            }';
            
            echo "   Reintentando sin colores específicos...\n";
            $result = $monday->rawQuery($roleUpdateSimple);
            
            if (isset($result['data']) && isset($result['data']['update_status_column'])) {
                echo "   ✅ Roles actualizados sin colores\n";
            } else {
                echo "   ❌ Error también sin colores: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
                echo "   ⚠️  Esta actualización puede requerir hacerse manualmente en la interfaz de Monday\n";
            }
        }
        
        echo "\n========================================\n";
        echo "    ACTUALIZACIÓN PARCIAL COMPLETADA    \n";
        echo "========================================\n";
        echo "⚠️  Si algunas actualizaciones fallaron,   \n";
        echo "   deberán hacerse manualmente en la     \n";
        echo "   interfaz de Monday.com               \n";
        echo "========================================\n";
        
        // Verificar si hubo cambios
        echo "\n4. VERIFICANDO ESTADO ACTUAL...\n";
        $checkQuery = '
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
        
        $checkResult = $monday->query($checkQuery);
        $updatedColumns = $checkResult['boards'][0]['columns'] ?? [];
        
        foreach ($updatedColumns as $column) {
            if ($column['id'] === 'color_mkypv3rg') {
                echo "   Clasificación actualizada:\n";
                $settings = json_decode($column['settings_str'], true);
                if (isset($settings['labels'])) {
                    echo "   - Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
                }
            } elseif ($column['id'] === 'color_mkyng649') {
                echo "   Rol Detectado actualizado:\n";
                $settings = json_decode($column['settings_str'], true);
                if (isset($settings['labels'])) {
                    echo "   - Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
                }
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar la actualización de etiquetas
$result = updateStatusLabels();

if ($result) {
    echo "\n✅ Proceso de actualización de etiquetas completado.\n";
    echo "   Puedes continuar con la actualización del webhook.\n";
} else {
    echo "\n⚠️  Se encontraron errores en la actualización.\n";
    echo "   Algunas actualizaciones requieren intervención manual.\n";
}

?>
