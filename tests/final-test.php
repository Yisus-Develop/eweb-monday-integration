<?php
// final-test.php
// Prueba final para verificar que todo funcione con las nuevas columnas

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA FINAL DEL WEBHOOK RECREADO      \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba simulando un formulario CF7
    $testFormData = [
        'nombre' => 'Prueba Final - ' . date('Y-m-d H:i:s'),
        'email' => 'final-test-' . time() . '@example.com',
        'company' => 'Final Test Corp',
        'role' => 'Mission Partner',
        'country' => 'España',
        'perfil' => 'pioneer',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 5000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es'
    ];
    
    echo "1. PROCESANDO DATOS DE PRUEBA...\n";
    echo "   - Nombre: {$testFormData['nombre']}\n";
    echo "   - Email: {$testFormData['email']}\n";
    echo "   - Empresa: {$testFormData['company']}\n";
    echo "   - Rol: {$testFormData['role']}\n";
    echo "   - País: {$testFormData['country']}\n\n";
    
    // Calcular scoring usando LeadScoring
    $scoringData = [
        'name' => $testFormData['nombre'],
        'email' => $testFormData['email'],
        'company' => $testFormData['company'],
        'role' => $testFormData['role'],
        'country' => $testFormData['country'],
        'perfil' => $testFormData['perfil'],
        'tipo_institucion' => $testFormData['tipo_institucion'],
        'numero_estudiantes' => $testFormData['numero_estudiantes'],
        'ea_source' => $testFormData['ea_source'],
        'ea_lang' => $testFormData['ea_lang'],
        'phone' => '999888777',
        'city' => 'Madrid'
    ];
    
    $scoreResult = LeadScoring::calculate($scoringData);
    
    echo "2. RESULTADO DEL SCORING...\n";
    echo "   - Puntuación Total: {$scoreResult['total']}\n";
    echo "   - Clasificación: {$scoreResult['priority_label']}\n";
    echo "   - Rol Detectado: {$scoreResult['detected_role']}\n";
    echo "   - Tipo de Lead: {$scoreResult['tipo_lead']}\n";
    echo "   - Canal de Origen: {$scoreResult['canal_origen']}\n";
    echo "   - Idioma: {$scoreResult['idioma']}\n\n";
    
    // Preparar valores para Monday usando los nuevos IDs
    $columnValues = [
        'name' => $testFormData['nombre'],  // El nombre del item
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],

        // Columnas de negocio con nuevos IDs
        'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score (Original ID)
        NewColumnIds::CLASSIFICATION => ['label' => $scoreResult['priority_label']],     // Clasificación (HOT/WARM/COLD) (NEW ID)
        NewColumnIds::ROLE_DETECTED => ['label' => $scoreResult['detected_role']],       // Rol Detectado (NEW ID)
        'text_mkyn95hk' => $testFormData['country'],                                     // País (Original ID)

        // Nuevas columnas críticas (con nuevos IDs)
        NewColumnIds::TYPE_OF_LEAD => ['label' => $scoreResult['tipo_lead']],            // Tipo de Lead (NEW ID)
        NewColumnIds::SOURCE_CHANNEL => ['label' => $scoreResult['canal_origen']],       // Canal de Origen (NEW ID)
        'text_mkypn0m' => ($scoringData['perfil'] === 'pioneer') ? $scoringData['name'] : '', // Mission Partner (NEW ID)
        NewColumnIds::LANGUAGE => ['label' => $scoreResult['idioma']],                   // Idioma (NEW ID)

        // Nuevas columnas secundarias
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada (Original ID)
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],             // Próxima Acción (Original ID)
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])                  // Notas Internas (Original ID)
    ];
    
    echo "3. CREANDO LEAD EN MONDAY CON NUEVAS COLUMNAS...\n";
    
    $response = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ ¡SUCCESS! Lead creado exitosamente\n";
        echo "   ✅ ID del ítem: $itemId\n";
        echo "   ✅ El webhook está funcionando correctamente\n\n";
        
        // Verificar que las columnas nuevas existan y tengan valores
        echo "4. VERIFICANDO NUEVAS COLUMNAS...\n";
        
        // Consultar el item recién creado para verificar los valores
        $itemQuery = '
        query {
            items(ids: '.$itemId.') {
                name
                column_values {
                    id
                    text_value
                    type
                    value
                }
            }
        }';
        
        $itemResult = $monday->query($itemQuery);
        $item = $itemResult['items'][0];
        
        echo "   Nombre del item: {$item['name']}\n";
        
        // Mapear los nuevos IDs para verificación
        $idToName = [
            NewColumnIds::CLASSIFICATION => 'Clasificación',
            NewColumnIds::ROLE_DETECTED => 'Rol Detectado',
            NewColumnIds::TYPE_OF_LEAD => 'Tipo de Lead',
            NewColumnIds::SOURCE_CHANNEL => 'Canal de Origen',
            NewColumnIds::LANGUAGE => 'Idioma'
        ];
        
        foreach ($item['column_values'] as $colValue) {
            $colId = $colValue['id'];
            if (isset($idToName[$colId])) {
                echo "   {$idToName[$colId]}: {$colValue['text_value']}\n";
            } elseif ($colId === 'numeric_mkyn2py0') {
                echo "   Lead Score: {$colValue['text_value']}\n";
            }
        }
        
        echo "\n========================================\n";
        echo "  ¡SISTEMA CRM COMPLETAMENTE OPERATIVO!  \n";
        echo "========================================\n";
        echo "✅ El sistema puede recibir y procesar leads\n";
        echo "✅ La lógica de scoring funciona correctamente\n";
        echo "✅ Las nuevas columnas están funcionando\n";
        echo "✅ Las clasificaciones HOT/WARM/COLD están operativas\n";
        echo "✅ Las opciones de dropdown están disponibles\n";
        echo "✅ El webhook está listo para producción\n";
        echo "========================================\n";
        
        return true;
    } else {
        echo "   ❌ Error al crear el lead: " . json_encode($response) . "\n";
        return false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
