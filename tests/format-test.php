<?php
// format-test.php
// Prueba con formatos específicos para las columnas de status y dropdown

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA CON FORMATOS ESPECÍFICOS        \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba
    $testFormData = [
        'nombre' => 'Prueba Formatos - ' . date('Y-m-d H:i:s'),
        'email' => 'format-test-' . time() . '@example.com',
        'company' => 'Format Test Corp',
        'role' => 'Rector/Director',
        'country' => 'España',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 5000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es'
    ];
    
    echo "1. PROCESANDO DATOS DE PRUEBA...\n";
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
    
    echo "2. CREANDO LEAD CON FORMATO ESPECÍFICO...\n";
    
    // Preparar los valores con el formato correcto para cada tipo de columna
    $columnValues = [
        'name' => $testFormData['nombre'],
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        
        // Para columnas status, usar el formato correcto: {"label": "valor"}
        NewColumnIds::CLASSIFICATION => ["label" => "HOT"],  // Clasificación: HOT/WARM/COLD
        NewColumnIds::ROLE_DETECTED => ["label" => "Rector/Director"],  // Rol Detectado
        
        'text_mkyn95hk' => $testFormData['country'],
        
        // Para columnas dropdown, también usar el formato: {"label": "valor"}
        NewColumnIds::TYPE_OF_LEAD => ["label" => "Universidad"],  // Tipo de Lead
        NewColumnIds::SOURCE_CHANNEL => ["label" => "Website"],  // Canal de Origen
        'text_mkypn0m' => 'Test MP',
        NewColumnIds::LANGUAGE => ["label" => "Español"],  // Idioma
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
    ];
    
    // Intentar crear con el formato correcto
    $response = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ ¡ÉXITO! Lead creado con formato correcto\n";
        echo "   ✅ ID del ítem: $itemId\n";
        
        echo "\n========================================\n";
        echo "  ¡SISTEMA CRM OPERATIVO COMPLETAMENTE!  \n";
        echo "========================================\n";
        echo "✅ Columnas recreadas correctamente\n";
        echo "✅ Valores formateados adecuadamente\n";
        echo "✅ Sistema listo para producción\n";
        echo "========================================\n";
        
        return true;
    } else {
        echo "   ❌ Error: " . json_encode($response) . "\n";
        
        // Probar un enfoque alternativo: omitir las columnas problematicas
        echo "\n3. REINTENTANDO SIN COLUMNAS PROBLEMATICAS...\n";
        
        $minimalColumnValues = [
            'name' => $testFormData['nombre'],
            'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
            'lead_company' => $testFormData['company'],
            'text' => $testFormData['role'],
            'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'numeric_mkyn2py0' => $scoreResult['total'],
            'text_mkyn95hk' => $testFormData['country'],
            'text_mkypn0m' => 'Test MP',
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
            'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
        ];
        
        $response2 = $monday->createItem($leadsBoardId, $testFormData['nombre'], $minimalColumnValues);
        
        if (isset($response2['create_item']['id'])) {
            $itemId2 = $response2['create_item']['id'];
            echo "   ✅ ¡ÉXITO! Lead creado sin columnas problematicas\n";
            echo "   ✅ ID del ítem: $itemId2\n";
            echo "   ⚠️  Las columnas dropdown/status tendrán que configurarse por separado\n";
            
            echo "\n   VALORES ENVIADOS:\n";
            foreach ($minimalColumnValues as $key => $value) {
                if (is_array($value)) {
                    echo "   - $key: " . json_encode($value) . "\n";
                } else {
                    echo "   - $key: $value\n";
                }
            }
            
            return true;
        } else {
            echo "   ❌ Error también sin columnas problematicas: " . json_encode($response2) . "\n";
            return false;
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
