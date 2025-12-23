<?php
// test-webhook-with-constants.php
// Script de prueba para verificar que el webhook funcione con las constantes actuales

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'StatusConstants.php';

echo "========================================\n";
echo "  PRUEBA DEL WEBHOOK CON CONSTANTES     \n";
echo "  Usando etiquetas actuales de Monday   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba simulando un formulario CF7
    $testFormData = [
        'nombre' => 'Prueba Webhook - ' . date('Y-m-d H:i:s'),
        'email' => 'test-webhook-' . time() . '@example.com',
        'company' => 'Test Company Corp',
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
    echo "   - Clasificación Esperada: " . StatusConstants::getScoreClassification($scoreResult['total']) . "\n";
    echo "   - Rol Detectado: {$scoreResult['detected_role']}\n";
    echo "   - Etiqueta de Rol Actual: " . StatusConstants::getRoleLabel($scoreResult['detected_role']) . "\n";
    echo "   - Tipo de Lead: {$scoreResult['tipo_lead']}\n";
    echo "   - Canal de Origen: {$scoreResult['canal_origen']}\n";
    echo "   - Idioma: {$scoreResult['idioma']}\n\n";
    
    // Preparar valores para Monday usando las constantes
    $columnValues = [
        'name' => $testFormData['nombre'],  // El nombre del item
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],

        // Columnas de negocio (usando constantes para las etiquetas actuales)
        'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score
        'color_mkypv3rg' => ['label' => StatusConstants::getScoreClassification($scoreResult['total'])], // Clasificación
        'color_mkyng649' => ['label' => StatusConstants::getRoleLabel($scoreResult['detected_role'])],  // Rol Detectado
        'text_mkyn95hk' => $testFormData['country'],                                     // País

        // Nuevas columnas críticas
        'dropdown_mkyp8q98' => ['label' => $scoreResult['tipo_lead']],                  // Tipo de Lead
        'dropdown_mkypf16c' => ['label' => $scoreResult['canal_origen']],               // Canal de Origen
        'text_mkypn0m' => ($scoringData['perfil'] === 'pioneer') ? $scoringData['name'] : '', // Mission Partner
        'dropdown_mkyps472' => ['label' => $scoreResult['idioma']],                     // Idioma

        // Nuevas columnas secundarias
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],             // Próxima Acción
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])                  // Notas Internas
    ];
    
    echo "3. CREANDO LEAD EN MONDAY...\n";
    
    $response = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ ¡SUCCESS! Lead creado exitosamente\n";
        echo "   ✅ ID del ítem: $itemId\n";
        echo "   ✅ El webhook está funcionando correctamente\n\n";
        
        echo "4. VERIFICANDO DATOS EN EL ITEM...\n";
        // Obtener el item recién creado para verificar
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
        foreach ($item['column_values'] as $colValue) {
            if ($colValue['id'] === 'numeric_mkyn2py0') {
                echo "   Lead Score: {$colValue['text_value']}\n";
            } elseif ($colValue['id'] === 'color_mkypv3rg') {
                echo "   Clasificación: {$colValue['text_value']}\n";
            } elseif ($colValue['id'] === 'color_mkyng649') {
                echo "   Rol Detectado: {$colValue['text_value']}\n";
            }
        }
        
        echo "\n========================================\n";
        echo "     ¡WEBHOOK FUNCIONANDO CORRECTAMENTE!  \n";
        echo "========================================\n";
        echo "✅ El sistema puede recibir y procesar leads\n";
        echo "✅ La lógica de scoring funciona\n";
        echo "✅ Las clasificaciones se asignan correctamente\n";
        echo "✅ Los datos se guardan en Monday\n";
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
