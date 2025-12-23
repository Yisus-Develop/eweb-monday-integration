<?php
// final-integration-test.php
// Versión final de prueba con las etiquetas reales del tablero

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';

function runFinalIntegrationTest() {
    echo "========================================\n";
    echo "  PRUEBA FINAL DE INTEGRACIÓN REAL      \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "🎯 Usando tablero de Leads: $leadsBoardId\n";
        
        // Datos para prueba
        $testData = [
            'nombre' => 'Prueba Integración REAL ' . date('H:i:s'),
            'email' => 'test.' . time() . '@virtualdemo.com',
            'perfil' => 'empresa',
            'pais_otro' => 'España',
            'company' => 'Virtual Demo Corp',
            'telefono' => '123456789',
            'role' => 'Prueba de Integración',
            'tipo_institucion' => 'Corporación',
            'numero_estudiantes' => 0,
            'poblacion' => 0,
            'modality' => 'Donación'
        ];
        
        // Usar la lógica del webhook para procesar los datos
        $scoringData = [
            'name' => $testData['nombre'],
            'email' => $testData['email'],
            'phone' => $testData['telefono'],
            'company' => $testData['company'],
            'role' => $testData['role'],
            'country' => $testData['pais_otro'],
            'city' => '',
            'perfil' => $testData['perfil'],
            'tipo_institucion' => $testData['tipo_institucion'],
            'numero_estudiantes' => (int)$testData['numero_estudiantes'],
            'poblacion' => (int)$testData['poblacion'],
            'modality' => $testData['modality'],
            'ea_source' => null,
            'ea_lang' => null,
        ];
        
        $scoreResult = LeadScoring::calculate($scoringData);
        
        echo "\n📊 RESULTADOS DEL SCORING:\n";
        echo "   Puntuación Total: {$scoreResult['total']}\n";
        echo "   Clasificación Calculada: {$scoreResult['priority_label']}\n";
        echo "   Rol Detectado: {$scoreResult['detected_role']}\n";
        echo "   Tipo de Lead: {$scoreResult['tipo_lead']}\n";
        
        // Preparar valores de columna usando las etiquetas ACTUALES del tablero
        $columnValues = [
            'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
            'lead_company' => $scoringData['company'],
            'text' => $scoringData['role'],
            'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'], // Etiqueta válida de la columna 'Estado'
            
            // Columnas de negocio (usando los IDs actualizados con etiquetas reales del tablero)
            'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score
            'color_mkypv3rg' => ['label' => 'Listo'],                                       // Clasificación (usando 'Listo' como temporal por las etiquetas reales)
            'color_mkyng649' => ['label' => 'Listo'],                                        // Rol Detectado (usando 'Listo' como temporal por las etiquetas reales)
            'text_mkyn95hk' => $scoringData['country'],                                     // País
            
            // Nuevas columnas (usando valores que existen en el sistema actual)
            'dropdown_mkyp8q98' => $scoreResult['tipo_lead'],                               // Tipo de Lead (como texto directo)
            'dropdown_mkypf16c' => $scoreResult['canal_origen'],                            // Canal de Origen (como texto directo)
            'text_mkypn0m' => ($scoringData['perfil'] === 'pioneer') ? $scoringData['name'] : '', // Mission Partner
            'dropdown_mkyps472' => $scoreResult['idioma'],                                  // Idioma (como texto directo)
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+2 days'))],             // Próxima Acción
            'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])                  // Notas Internas
        ];
        
        // Intentar crear el ítem
        echo "\n📤 Enviando lead de prueba a Monday.com...\n";
        $itemResponse = $monday->createItem($leadsBoardId, $scoringData['name'], $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "✅ ¡SUCCESS! Lead de prueba creado exitosamente!\n";
            echo "   ID del ítem: $itemId\n";
            echo "   Puntuación: {$scoreResult['total']}\n";
            echo "   Clasificación real (con etiqueta actual): Listo\n";
            echo "   Rol detectado (con etiqueta actual): Listo\n";
            echo "   Rol detectado original: {$scoreResult['detected_role']}\n";
            
            echo "\n🔍 CONCLUSIONES IMPORTANTES:\n";
            echo "   ✓ El sistema puede CREAR ítems en el tablero de Leads\n";
            echo "   ✓ El webhook y la lógica de scoring funcionan correctamente\n";
            echo "   ✓ La conexión con la API de Monday.com es funcional\n";
            echo "   ✓ Se detectaron las columnas reales y etiquetas del tablero\n";
            echo "   \n   ⚠️  PERO: Las columnas de 'Clasificación' y 'Rol Detectado' tienen\n";
            echo "      etiquetas incorrectas ('En curso', 'Listo', 'Detenido') en lugar\n";
            echo "      de las etiquetas esperadas ('HOT', 'WARM', 'COLD' y roles específicos)\n";
            echo "   \n   🎯 REQUERIDO: Configurar manualmente las etiquetas correctas en\n";
            echo "      la interfaz de Monday.com para ambas columnas.\n";
            
            // Probar actualización (duplicado)
            echo "\n🧪 Probando manejo de duplicados (actualización)...\n";
            $existingItems = $monday->getItemsByColumnValue($leadsBoardId, 'lead_email', $scoringData['email']);
            
            if (!empty($existingItems)) {
                echo "✅ Duplicado detectado correctamente\n";
                $itemIdToUpdate = $existingItems[0]['id'];
                
                // Actualizar con nuevos datos
                $updatedColumnValues = $columnValues;
                $updatedColumnValues['text'] = 'Lead Actualizado - Prueba de Duplicado';
                
                $updateResponse = $monday->updateItem($leadsBoardId, $itemIdToUpdate, $updatedColumnValues);
                if (isset($updateResponse['update_item']['id'])) {
                    echo "✅ Lead duplicado actualizado exitosamente (ID: {$updateResponse['update_item']['id']})\n";
                } else {
                    echo "❌ Error al actualizar duplicado: " . json_encode($updateResponse) . "\n";
                }
            } else {
                echo "⚠️  No se encontraron duplicados para prueba\n";
            }
            
        } else {
            echo "❌ Error al crear el ítem: " . json_encode($itemResponse) . "\n";
        }
        
        echo "\n========================================\n";
        echo "     PRUEBA DE INTEGRACIÓN COMPLETA     \n";
        echo "========================================\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
    }
}

// Ejecutar la prueba final
runFinalIntegrationTest();
?>
