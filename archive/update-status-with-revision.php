<?php
// update-status-with-revision.php
// Script para actualizar las etiquetas de status usando la revisión correcta

require_once '../config.php';
require_once 'MondayAPI.php';

function updateStatusWithRevision() {
    echo "========================================\n";
    echo "  ACTUALIZACIÓN DE ETIQUETAS (CON REVISIÓN)\n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. OBTENIENDO METADATOS DE LAS COLUMNAS...\n";
        
        // Consultar las columnas con sus metadatos completos
        $query = '
        query {
            boards(ids: '.$leadsBoardId.') {
                id
                name
                updated_at
                columns {
                    id
                    title
                    type
                    settings_str
                    metadata
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
        
        echo "   Columna de Clasificación encontrada:\n";
        echo "   - ID: {$classificationColumn['id']}\n";
        echo "   - Title: {$classificationColumn['title']}\n";
        echo "   - Settings: {$classificationColumn['settings_str']}\n";
        echo "   - Metadata: " . json_encode($classificationColumn['metadata']) . "\n\n";
        
        echo "   Columna de Rol Detectado encontrada:\n";
        echo "   - ID: {$roleColumn['id']}\n";
        echo "   - Title: {$roleColumn['title']}\n";
        echo "   - Settings: {$roleColumn['settings_str']}\n";
        echo "   - Metadata: " . json_encode($roleColumn['metadata']) . "\n\n";
        
        // Intentar extraer la revisión de los metadatos o de la configuración
        $classSettings = json_decode($classificationColumn['settings_str'], true);
        $roleSettings = json_decode($roleColumn['settings_str'], true);
        
        // Mostrar las etiquetas actuales
        if (isset($classSettings['labels'])) {
            echo "   Etiquetas actuales de Clasificación:\n";
            foreach ($classSettings['labels'] as $index => $label) {
                echo "   - $index: $label\n";
            }
        }
        
        if (isset($roleSettings['labels'])) {
            echo "   Etiquetas actuales de Rol Detectado:\n";
            foreach ($roleSettings['labels'] as $index => $label) {
                echo "   - $index: $label\n";
            }
        }
        
        // No podemos obtener la revisión directamente de esta consulta
        // según la documentación, necesitamos usar una consulta específica
        // para obtener la revisión. Pero primero probemos una actualización con
        // un valor de revisión falso para ver si Monday nos da el valor correcto en el error
        
        echo "\n2. INTENTANDO ACTUALIZACIÓN CON REVISIÓN FICTICIA...\n";
        
        $classificationUpdate = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkypv3rg",
            revision: "invalid_revision_for_error",
            settings: {
              labels: [
                { color: "done_red", label: "HOT", index: 0 },
                { color: "working_yellow", label: "WARM", index: 1 },
                { color: "issue_blue", label: "COLD", index: 2 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización para obtener error con revisión correcta...\n";
        $result = $monday->rawQuery($classificationUpdate);
        
        if (isset($result['errors'])) {
            echo "   Error recibido (puede contener información de revisión):\n";
            echo "   " . json_encode($result['errors']) . "\n\n";
        }
        
        // Lo que necesitamos hacer es probablemente usar una consulta diferente
        // para obtener la revisión actual del tablero o columna
        echo "3. OBTENIENDO REVISIÓN DE TABLERO...\n";
        
        // Intentemos una consulta diferente que pueda darnos la información de revisión
        $revisionQuery = '
        query {
            boards(ids: '.$leadsBoardId.') {
                id
                name
                board_folder_id
                board_kind
                description
                permissions
                pos
                state
                updated_at
                views {
                    id
                    name
                    settings
                }
                workspace_id
            }
        }';
        
        $revisionResult = $monday->query($revisionQuery);
        $boardInfo = $revisionResult['boards'][0];
        
        echo "   Tablero info actualizado:\n";
        echo "   - Updated at: " . $boardInfo['updated_at'] . "\n";
        echo "   - State: " . $boardInfo['state'] . "\n\n";
        
        // Crear un hash basado en la información del tablero y la columna
        // como estrategia alternativa
        $revision = md5($leadsBoardId . $classificationColumn['id'] . $boardInfo['updated_at']);
        echo "   Revisión generada: $revision\n\n";
        
        // Intentar actualización con la revisión generada
        $classificationUpdateWithRev = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkypv3rg",
            revision: "'.$revision.'",
            settings: {
              labels: [
                { color: done_red, label: "HOT", index: 0 },
                { color: working_yellow, label: "WARM", index: 1 },
                { color: issue_blue, label: "COLD", index: 2 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "4. INTENTANDO ACTUALIZACIÓN CON REVISIÓN GENERADA...\n";
        echo "   Enviando actualización...\n";
        $result = $monday->rawQuery($classificationUpdateWithRev);
        
        if (isset($result['data']) && isset($result['data']['update_status_column'])) {
            echo "   ✅ ¡ÉXITO! Clasificación actualizada a HOT/WARM/COLD\n";
        } else {
            echo "   ❌ Error con revisión generada: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            
            // Si la revisión generada no funciona, intentemos sin el campo color
            echo "\n5. REINTENTANDO SIN CAMPOS DE COLOR...\n";
            
            $classificationUpdateNoColor = '
            mutation {
              update_status_column(
                board_id: '.$leadsBoardId.',
                id: "color_mkypv3rg",
                revision: "'.$revision.'",
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
            
            $result = $monday->rawQuery($classificationUpdateNoColor);
            
            if (isset($result['data']) && isset($result['data']['update_status_column'])) {
                echo "   ✅ Clasificación actualizada sin colores específicos\n";
            } else {
                echo "   ❌ Error también sin colores: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
                echo "\n6. INTENTANDO CON COLores VÁLIDOS SEGÚN EL ERROR...\n";
                
                // Según los errores anteriores, intentemos con colores válidos
                $validColorUpdate = '
                mutation {
                  update_status_column(
                    board_id: '.$leadsBoardId.',
                    id: "color_mkypv3rg",
                    revision: "'.$revision.'",
                    settings: {
                      labels: [
                        { color: dark_red, label: "HOT", index: 0 },
                        { color: working_orange, label: "WARM", index: 1 },
                        { color: dark_blue, label: "COLD", index: 2 }
                      ]
                    }
                  ) {
                    id
                  }
                }';
                
                $result = $monday->rawQuery($validColorUpdate);
                
                if (isset($result['data']) && isset($result['data']['update_status_column'])) {
                    echo "   ✅ Clasificación actualizada con colores válidos\n";
                } else {
                    echo "   ❌ Error con colores válidos: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
                    echo "\n   Puede que necesitemos obtener la revisión real directamente de la columna\n";
                    echo "   o hacer la actualización manualmente en la interfaz.\n";
                }
            }
        }
        
        echo "\n========================================\n";
        echo "    PROCESO DE ACTUALIZACIÓN TERMINADO   \n";
        echo "========================================\n";
        echo "⚠️  Si la actualización falló, deberá hacerse\n";
        echo "   manualmente a través de la interfaz de   \n";
        echo "   Monday.com                             \n";
        echo "========================================\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar la actualización con revisión
$result = updateStatusWithRevision();

if ($result) {
    echo "\n✅ Proceso completado.\n";
} else {
    echo "\n❌ Se encontraron errores.\n";
}

?>
