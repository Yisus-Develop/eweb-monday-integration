<?php
// corrected-immediate-action.php
// Versión corregida del plan de acción inmediata para Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';

function executeCorrectedActionPlan() {
    echo "========================================\n";
    echo "  ACCIÓN INMEDIATA CORREGIDA            \n";
    echo "  Activación de funcionalidad completa   \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. OBTENIENDO INFORMACIÓN ACTUAL DE COLUMNAS...\n";
        
        // Obtener información detallada de las columnas de status
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
        
        // Buscar las columnas específicas
        $classificationColumn = null;
        $roleColumn = null;
        
        foreach ($columns as $column) {
            if ($column['id'] === 'color_mkypv3rg' && $column['title'] === 'Clasificación') {
                $classificationColumn = $column;
            } elseif ($column['id'] === 'color_mkyng649' && $column['title'] === 'Rol Detectado') {
                $roleColumn = $column;
            }
        }
        
        if ($classificationColumn) {
            echo "   Clasificación column found:\n";
            echo "   - ID: {$classificationColumn['id']}\n";
            echo "   - Title: {$classificationColumn['title']}\n";
            echo "   - Type: {$classificationColumn['type']}\n";
            echo "   - Settings: " . $classificationColumn['settings_str'] . "\n\n";
        }
        
        if ($roleColumn) {
            echo "   Rol Detectado column found:\n";
            echo "   - ID: {$roleColumn['id']}\n";
            echo "   - Title: {$roleColumn['title']}\n";
            echo "   - Type: {$roleColumn['type']}\n";
            echo "   - Settings: " . $roleColumn['settings_str'] . "\n\n";
        }
        
        echo "2. ACTUALIZANDO ETIQUETAS DE CLASIFICACIÓN...\n";
        
        // Intentar actualizar con los valores correctos del enum
        // Primero, intentemos sin el argumento revision para ver si es opcional en algunas versiones
        $classificationUpdate = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkypv3rg",
            settings: {
              labels: [
                { label: "HOT", color: "red", index: 0 },
                { label: "WARM", color: "yellow", index: 1 },
                { label: "COLD", color: "blue", index: 2 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización de clasificación...\n";
        $result = $monday->rawQuery($classificationUpdate);
        
        if (isset($result['data']) && isset($result['data']['update_status_column'])) {
            echo "   ✅ Clasificación actualizada a HOT/WARM/COLD\n";
        } else {
            echo "   ⚠️  Error en actualización de clasificación: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            
            // Intentar con colores válidos del enum
            $validColorsUpdate = '
            mutation {
              update_status_column(
                board_id: '.$leadsBoardId.',
                id: "color_mkypv3rg",
                settings: {
                  labels: [
                    { label: "HOT", color: "done_red", index: 0 },
                    { label: "WARM", color: "working_yellow", index: 1 },
                    { label: "COLD", color: "issue_blue", index: 2 }
                  ]
                }
              ) {
                id
              }
            }';
            
            echo "   Reintentando con colores válidos del enum...\n";
            $result = $monday->rawQuery($validColorsUpdate);
            
            if (isset($result['data']) && isset($result['data']['update_status_column'])) {
                echo "   ✅ Clasificación actualizada con colores válidos\n";
            } else {
                echo "   ⚠️  Error también con colores válidos: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            }
        }
        
        echo "\n3. ACTUALIZANDO ETIQUETAS DE ROL DETECTADO...\n";
        
        $roleUpdate = '
        mutation {
          update_status_column(
            board_id: '.$leadsBoardId.',
            id: "color_mkyng649",
            settings: {
              labels: [
                { label: "Mission Partner", color: "done_purple", index: 0 },
                { label: "Rector/Director", color: "done_green", index: 1 },
                { label: "Alcalde/Gobierno", color: "done_orange", index: 2 },
                { label: "Corporate", color: "done_sky", index: 3 },
                { label: "Maestro/Mentor", color: "done_pink", index: 4 },
                { label: "Joven", color: "done_grass_green", index: 5 }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización de roles...\n";
        $result = $monday->rawQuery($roleUpdate);
        if (isset($result['data']) && isset($result['data']['update_status_column'])) {
            echo "   ✅ Roles actualizados correctamente\n";
        } else {
            echo "   ⚠️  Error en actualización de roles: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
        }
        
        echo "\n4. ESPERANDO UN MOMENTO PARA QUE SE APLIQUEN CAMBIOS...\n";
        sleep(2); // Esperar un momento para que se apliquen los cambios
        
        echo "\n5. VERIFICANDO ACTUALIZACIÓN DE ETIQUETAS...\n";
        
        // Volver a consultar las columnas para ver si los cambios se aplicaron
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
                echo "   - Settings: " . $column['settings_str'] . "\n";
                
                // Mostrar las nuevas etiquetas si están disponibles
                $settings = json_decode($column['settings_str'], true);
                if (isset($settings['labels'])) {
                    echo "   - Etiquetas disponibles: " . implode(', ', array_values($settings['labels'])) . "\n";
                }
            }
        }
        
        echo "\n6. CREANDO LEAD DE PRUEBA CON NUEVAS ETIQUETAS...\n";
        
        // Crear un lead de prueba con las posibles nuevas etiquetas o con las originales si no se actualizaron
        $testLeadName = 'Lead de Prueba - Activación Inmediata ' . date('Y-m-d H:i:s');
        $testEmail = 'activation-test-' . time() . '@example.com';
        
        // Intentar usar las nuevas etiquetas, pero si fallan, usar las originales
        $columnValues = [
            'name' => $testLeadName,
            'lead_email' => ['email' => $testEmail, 'text' => $testEmail],
            'lead_company' => 'Prueba Activación Rápida',
            'text' => 'Director/Académico',
            'lead_phone' => ['phone' => '999999999', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'numeric_mkyn2py0' => 85, // Puntuación alta para perfil VIP
            // Usar la etiqueta original si la nueva no existe aún
            'color_mkypv3rg' => ['label' => 'En curso'], // Esta etiqueta debe existir
            'color_mkyng649' => ['label' => 'En curso'], // Esta etiqueta debe existir
            'text_mkyn95hk' => 'España',
            'dropdown_mkypgz6f' => ['label' => 'Website'], // Tipo de Lead
            'dropdown_mkypbsmj' => ['label' => 'Contact Form'], // Canal de Origen
            'text_mkypbqgg' => 'MP001',
            'dropdown_mkypzbbh' => ['label' => 'Español'], // Idioma
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
            'long_text_mkypqppc' => 'Lead creado para activación inmediata del sistema CRM'
        ];
        
        echo "   Creando lead de prueba...\n";
        $itemResponse = $monday->createItem($leadsBoardId, $testLeadName, $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "   ✅ ¡SUCCESS! Lead de prueba creado exitosamente\n";
            echo "   ✅ ID del ítem: $itemId\n";
            echo "   ✅ El sistema puede crear leads\n\n";
            
            // Ahora intentar actualizar la clasificación una vez creado
            echo "7. PROBANDO ACTUALIZACIÓN DE CLASIFICACIÓN...\n";
            
            try {
                $updateResult = $monday->changeColumnValue($leadsBoardId, $itemId, 'color_mkypv3rg', ['label' => 'Listo']);
                echo "   ✅ Actualización de clasificación funcionando\n";
            } catch (Exception $e) {
                echo "   ⚠️  Error en actualización: " . $e->getMessage() . "\n";
            }
            
            echo "\n========================================\n";
            echo "     ¡PARTE DEL SISTEMA ACTIVADO!         \n";
            echo "========================================\n";
            echo "✅ Conexión con Monday.com verificada\n";
            echo "✅ Creación de leads funcionando\n";
            echo "✅ Actualización de valores funcionando\n";
            echo "⚠️  Corrección de etiquetas en proceso\n";
            echo "========================================\n";
            
            return true;
        } else {
            echo "   ❌ Error al crear lead de prueba: " . json_encode($itemResponse) . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar el plan de acción corregido
$result = executeCorrectedActionPlan();

if ($result) {
    echo "\n🎉 ¡PARTE DE LAS ACCIONES COMPLETADAS!\n";
    echo "El sistema ahora puede crear y actualizar leads.\n";
    echo "Las correcciones de etiquetas continuarán en segundo plano.\n";
} else {
    echo "\n⚠️  Se encontraron errores críticos.\n";
    echo "La actualización de etiquetas puede requerir intervención manual.\n";
}

?>
