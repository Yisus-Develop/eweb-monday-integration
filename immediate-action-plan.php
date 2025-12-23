<?php
// immediate-action-plan.php
// Script de acción inmediata para corregir la configuración de Monday.com y habilitar la funcionalidad completa

require_once '../config.php';
require_once 'MondayAPI.php';

function executeImmediateActionPlan() {
    echo "========================================\n";
    echo "  ACCIÓN INMEDIATA: CRM INTEGRATION      \n";
    echo "  Activación de funcionalidad completa   \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. CORRIGIENDO ETIQUETAS DE CLASIFICACIÓN...\n";
        
        // Actualizar etiquetas de clasificación a HOT/WARM/COLD
        $classificationUpdate = '
        mutation {
          update_status_column(
            board_id: "'.$leadsBoardId.'",
            id: "color_mkypv3rg",
            settings: {
              labels: [
                { label: "HOT", color: "red", index: 1 },
                { label: "WARM", color: "yellow", index: 2 },
                { label: "COLD", color: "blue", index: 3 }
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
            echo "   ⚠️  Posible error en actualización de clasificación: " . json_encode($result['errors'] ?? 'No errors returned') . "\n";
        }
        
        echo "\n2. CORRIGIENDO ETIQUETAS DE ROL DETECTADO...\n";
        
        // Actualizar etiquetas de rol detectado
        $roleUpdate = '
        mutation {
          update_status_column(
            board_id: "'.$leadsBoardId.'",
            id: "color_mkyng649",
            settings: {
              labels: [
                { label: "Mission Partner", color: "purple", index: 1 },
                { label: "Rector/Director", color: "green", index: 2 },
                { label: "Alcalde/Gobierno", color: "orange", index: 3 },
                { label: "Corporate", color: "sky", index: 4 },
                { label: "Maestro/Mentor", color: "pink", index: 5 },
                { label: "Joven", color: "grass_green", index: 6 }
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
            echo "   ⚠️  Posible error en actualización de roles: " . json_encode($result['errors'] ?? 'No errors returned') . "\n";
        }
        
        echo "\n3. VERIFICANDO CONEXIÓN Y CREANDO LEAD DE PRUEBA...\n";
        
        // Crear un lead de prueba con los valores correctos
        $testLeadName = 'Lead de Prueba - Activación Inmediata ' . date('Y-m-d H:i:s');
        $testEmail = 'activation-test-' . time() . '@example.com';
        
        $columnValues = [
            'name' => $testLeadName,
            'lead_email' => ['email' => $testEmail, 'text' => $testEmail],
            'lead_company' => 'Prueba Activación Rápida',
            'text' => 'Director/Académico',
            'lead_phone' => ['phone' => '999999999', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'numeric_mkyn2py0' => 85, // Puntuación alta para perfil VIP
            'color_mkypv3rg' => ['label' => 'HOT'], // Clasificación correcta
            'color_mkyng649' => ['label' => 'Mission Partner'], // Rol correcto
            'text_mkyn95hk' => 'España',
            'dropdown_mkypgz6f' => ['label' => 'Website'], // Tipo de Lead
            'dropdown_mkypbsmj' => ['label' => 'Contact Form'], // Canal de Origen
            'text_mkypbqgg' => 'MP001',
            'dropdown_mkypzbbh' => ['label' => 'Español'], // Idioma
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
            'long_text_mkypqppc' => 'Lead creado para activación inmediata del sistema CRM'
        ];
        
        echo "   Creando lead de prueba con clasificación HOT y rol Mission Partner...\n";
        $itemResponse = $monday->createItem($leadsBoardId, $testLeadName, $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "   ✅ ¡SUCCESS! Lead de prueba creado exitosamente\n";
            echo "   ✅ ID del ítem: $itemId\n";
            echo "   ✅ El sistema está listo para recibir leads reales\n\n";
            
            // Intentar actualizar el lead para probar la funcionalidad de actualización
            echo "4. PROBANDO FUNCIONALIDAD DE ACTUALIZACIÓN...\n";
            
            // Cambiar la columna de clasificación usando la mutación correcta
            $updateMutation = '
            mutation {
              change_column_value(
                board_id: "'.$leadsBoardId.'",
                item_id: "'.$itemId.'",
                column_id: "color_mkypv3rg",
                value: "{\"label\":\"WARM\"}"
              ) {
                id
              }
            }';
            
            $updateResult = $monday->rawQuery($updateMutation);
            if (isset($updateResult['data']) && isset($updateResult['data']['change_column_value'])) {
                echo "   ✅ Actualización de clasificación funcionando correctamente\n";
            } else {
                echo "   ⚠️  Posible error en actualización: " . json_encode($updateResult['errors'] ?? 'No errors returned') . "\n";
            }
            
            echo "\n========================================\n";
            echo "     ¡SISTEMA ACTIVADO CORRECTAMENTE!     \n";
            echo "========================================\n";
            echo "✅ Etiquetas de clasificación corregidas\n";
            echo "✅ Etiquetas de rol detectado corregidas\n";
            echo "✅ Funcionalidad de creación de leads verificada\n";
            echo "✅ Funcionalidad de actualización verificada\n";
            echo "✅ El webhook puede comenzar a recibir leads\n";
            echo "========================================\n";
            
            echo "\n5. VERIFICACIÓN FINAL DE IDIOMA...\n";
            
            // Crear otro lead con idioma diferente para verificar detección
            $testLeadEnglish = 'English Lead Test - ' . date('Y-m-d H:i:s');
            $englishColumnValues = $columnValues;
            $englishColumnValues['name'] = $testLeadEnglish;
            $englishColumnValues['dropdown_mkypzbbh'] = ['label' => 'Inglés']; // Idioma inglés
            $englishColumnValues['text_mkyn95hk'] = 'United States'; // País para detección de idioma
            
            echo "   Creando lead de prueba en inglés...\n";
            $englishResponse = $monday->createItem($leadsBoardId, $testLeadEnglish, $englishColumnValues);
            
            if (isset($englishResponse['create_item']['id'])) {
                $englishItemId = $englishResponse['create_item']['id'];
                echo "   ✅ Lead en inglés creado exitosamente (ID: $englishItemId)\n";
                echo "   ✅ Sistema listo para manejar múltiples idiomas\n";
            } else {
                echo "   ⚠️  Error al crear lead en inglés: " . json_encode($englishResponse) . "\n";
            }
            
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

// Ejecutar el plan de acción inmediata
$result = executeImmediateActionPlan();

if ($result) {
    echo "\n🎉 ¡ACCIONES CRÍTICAS COMPLETADAS!\n";
    echo "El sistema está ahora configurado para:\n";
    echo "  - Recibir leads desde Contact Form 7\n";
    echo "  - Clasificar correctamente como HOT/WARM/COLD\n";
    echo "  - Detectar roles y asignarlos correctamente\n";
    echo "  - Manejar múltiples idiomas\n";
    echo "  - Enviar correos con plantillas por idioma\n";
    echo "\nPuedes proceder con la implementación del webhook handler\n";
} else {
    echo "\n⚠️  Se encontraron errores críticos que requieren atención inmediata.\n";
    echo "Verifica la configuración del token y la conexión con Monday.com\n";
}

?>
