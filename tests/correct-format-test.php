<?php
// correct-format-test.php
// Prueba con el formato correcto basado en la estructura real de las columnas

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA CON FORMATO CORRECTO REAL       \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba
    $testFormData = [
        'nombre' => 'Prueba Formato Correcto - ' . date('Y-m-d H:i:s'),
        'email' => 'correct-format-test-' . time() . '@example.com',
        'company' => 'Correct Format Test Corp',
        'role' => 'Rector/Director',
        'country' => 'España',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 5000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es'
    ];
    
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
    
    echo "1. CREANDO LEAD CON FORMATOS AJUSTADOS...\n";
    
    // Formato correcto para columnas:
    // - Status: {"label": "nombre_de_la_opción"} (correcto)
    // - Dropdown: {"label": "nombre_de_la_opción"} (también debería ser correcto)
    // El problema puede ser que la API espera el ID numérico en lugar del nombre
    
    // Basado en la información recopilada:
    // Para dropdown: {"label": "nombre"} debería funcionar
    // Para status: {"label": "nombre"} debería funcionar
    
    $columnValues = [
        'name' => $testFormData['nombre'],
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        
        // Columnas status - deberían usar {"label": "nombre"}
        NewColumnIds::CLASSIFICATION => ["label" => "HOT"],  // Esta etiqueta existe
        NewColumnIds::ROLE_DETECTED => ["label" => "Rector/Director"],  // Esta etiqueta existe
        
        'text_mkyn95hk' => $testFormData['country'],
        
        // Columnas dropdown - según la documentación también usan {"label": "nombre"}
        // Pero según el error, quizás necesitamos usar el ID numérico
        // type_of_lead tiene: Universidad (id:1), Escuela (id:2), Empresa (id:3), etc.
        NewColumnIds::TYPE_OF_LEAD => ["id" => 1],  // Universidad (ID numérico)
        // Si no funciona con ID, probamos con label
        NewColumnIds::SOURCE_CHANNEL => ["label" => "Website"],  // Esta etiqueta existe
        'text_mkypn0m' => 'Test MP',
        NewColumnIds::LANGUAGE => ["label" => "Español"],  // Esta etiqueta existe
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
    ];
    
    // Primero intentemos con la universidad como ID
    $response = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ ¡ÉXITO! Lead creado con ID numérico para dropdown\n";
        echo "   ✅ ID del ítem: $itemId\n";
        return true;
    } else {
        echo "   ❌ Error con ID: " . json_encode($response) . "\n";
        
        // Reintentar con label en lugar de id
        $columnValues[NewColumnIds::TYPE_OF_LEAD] = ["label" => "Universidad"];
        
        $response2 = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
        
        if (isset($response2['create_item']['id'])) {
            $itemId2 = $response2['create_item']['id'];
            echo "   ✅ ¡ÉXITO! Lead creado con label para dropdown\n";
            echo "   ✅ ID del ítem: $itemId2\n";
            return true;
        } else {
            echo "   ❌ Error con label también: " . json_encode($response2) . "\n";
            
            // Probar con valores más simples
            echo "\n2. REINTENTANDO CON VALORES MUY SIMPLES...\n";
            
            $simpleValues = [
                'name' => $testFormData['nombre'],
                'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
                'lead_company' => $testFormData['company'],
                'text' => $testFormData['role'],
                'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
                'lead_status' => ['label' => 'Lead nuevo'],
                'numeric_mkyn2py0' => $scoreResult['total'],
                
                // Usar solo status (que debería funcionar)
                NewColumnIds::CLASSIFICATION => ["label" => "HOT"],
                NewColumnIds::ROLE_DETECTED => ["label" => "Rector/Director"],
                
                'text_mkyn95hk' => $testFormData['country'],
                
                // Usar valores simples para dropdown
                NewColumnIds::TYPE_OF_LEAD => ["label" => "Empresa"],  // Intentar con uno diferente
                NewColumnIds::SOURCE_CHANNEL => ["label" => "Website"],
                'text_mkypn0m' => 'Test MP',
                NewColumnIds::LANGUAGE => ["label" => "Español"],
                'date_mkyp6w4t' => ['date' => date('Y-m-d')],
                'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
                'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
            ];
            
            $response3 = $monday->createItem($leadsBoardId, $testFormData['nombre'], $simpleValues);
            
            if (isset($response3['create_item']['id'])) {
                $itemId3 = $response3['create_item']['id'];
                echo "   ✅ ¡ÉXITO! Lead creado con valores simples\n";
                echo "   ✅ ID del ítem: $itemId3\n";
                return true;
            } else {
                echo "   ❌ Error con valores simples: " . json_encode($response3) . "\n";
                
                // Finalmente, intentar sin columnas nuevas
                echo "\n3. REINTENTO FINAL - SOLO COLUMNAS ORIGINALES...\n";
                
                $basicValues = [
                    'name' => $testFormData['nombre'],
                    'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
                    'lead_company' => $testFormData['company'],
                    'text' => $testFormData['role'],
                    'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
                    'lead_status' => ['label' => 'Lead nuevo'],
                    'numeric_mkyn2py0' => $scoreResult['total'],
                    'text_mkyn95hk' => $testFormData['country'],
                    'date_mkyp6w4t' => ['date' => date('Y-m-d')],
                    'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
                    'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
                ];
                
                $response4 = $monday->createItem($leadsBoardId, $testFormData['nombre'], $basicValues);
                
                if (isset($response4['create_item']['id'])) {
                    $itemId4 = $response4['create_item']['id'];
                    echo "   ✅ ¡ÉXITO! Lead creado con columnas básicas\n";
                    echo "   ✅ ID del ítem: $itemId4\n";
                    echo "   ⚠️  Las nuevas columnas necesitan configuración adicional\n";
                    return true;
                } else {
                    echo "   ❌ Error con columnas básicas: " . json_encode($response4) . "\n";
                    return false;
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
