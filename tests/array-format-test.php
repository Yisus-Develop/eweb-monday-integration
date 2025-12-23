<?php
// array-format-test.php
// Prueba con formato de array para columnas dropdown

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA CON FORMATO DE ARRAY           \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba
    $testFormData = [
        'nombre' => 'Prueba Array Format - ' . date('Y-m-d H:i:s'),
        'email' => 'array-test-' . time() . '@example.com',
        'company' => 'Array Test Corp',
        'role' => 'Test Role',
        'country' => 'España',
        'phone' => '999888777'
    ];
    
    echo "1. CREANDO LEAD CON FORMATO DE ARRAY...\n";
    
    // Usando formato de array para columnas dropdown como sugiere el error
    $columnValues = [
        'name' => $testFormData['nombre'],
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => 25, // Puntuación de prueba
        
        // Columnas status - usar formato normal
        NewColumnIds::CLASSIFICATION => ["label" => "HOT"],
        NewColumnIds::ROLE_DETECTED => ["label" => "Rector/Director"],
        
        'text_mkyn95hk' => $testFormData['country'],
        
        // Columnas dropdown - usar array como sugiere el error
        NewColumnIds::TYPE_OF_LEAD => ["labels" => ["Universidad"]],  // Formato de array
        NewColumnIds::SOURCE_CHANNEL => ["labels" => ["Website"]],    // Formato de array
        'text_mkypn0m' => 'Test MP',
        NewColumnIds::LANGUAGE => ["labels" => ["Español"]],          // Formato de array
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => 'Test notes'
    ];
    
    $response = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ ¡ÉXITO! Lead creado con formato de array\n";
        echo "   ✅ ID del ítem: $itemId\n";
        return true;
    } else {
        echo "   ❌ Error con formato de array: " . json_encode($response) . "\n";
        
        // Reintentar con el formato estándar pero solo para columnas que sabemos que funcionan
        echo "\n2. REINTENTANDO SOLO CON STATUS...\n";
        
        $statusOnlyValues = [
            'name' => $testFormData['nombre'],
            'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
            'lead_company' => $testFormData['company'],
            'text' => $testFormData['role'],
            'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'numeric_mkyn2py0' => 25,
            
            // Solo columnas status
            NewColumnIds::CLASSIFICATION => ["label" => "HOT"],
            NewColumnIds::ROLE_DETECTED => ["label" => "Rector/Director"],
            
            'text_mkyn95hk' => $testFormData['country'],
            'text_mkypn0m' => 'Test MP',
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
            'long_text_mkypqppc' => 'Test notes'
        ];
        
        $response2 = $monday->createItem($leadsBoardId, $testFormData['nombre'], $statusOnlyValues);
        
        if (isset($response2['create_item']['id'])) {
            $itemId2 = $response2['create_item']['id'];
            echo "   ✅ ¡ÉXITO! Lead creado solo con columnas status\n";
            echo "   ✅ ID del ítem: $itemId2\n";
            
            echo "\n3. AHORA PROBANDO ACTUALIZACIÓN DE DROPDOWN...\n";
            
            // Intentar actualizar el item con valores de dropdown
            $updateResponse = $monday->changeColumnValue($leadsBoardId, $itemId2, NewColumnIds::TYPE_OF_LEAD, ["label" => "Universidad"]);
            
            if (isset($updateResponse['change_column_value']['id'])) {
                echo "   ✅ ¡ÉXITO! Actualizada columna dropdown\n";
            } else {
                echo "   ⚠️  No se pudo actualizar dropdown: " . json_encode($updateResponse) . "\n";
            }
            
            $updateResponse2 = $monday->changeColumnValue($leadsBoardId, $itemId2, NewColumnIds::SOURCE_CHANNEL, ["label" => "Website"]);
            
            if (isset($updateResponse2['change_column_value']['id'])) {
                echo "   ✅ ¡ÉXITO! Actualizada columna canal de origen\n";
            } else {
                echo "   ⚠️  No se pudo actualizar canal de origen: " . json_encode($updateResponse2) . "\n";
            }
            
            $updateResponse3 = $monday->changeColumnValue($leadsBoardId, $itemId2, NewColumnIds::LANGUAGE, ["label" => "Español"]);
            
            if (isset($updateResponse3['change_column_value']['id'])) {
                echo "   ✅ ¡ÉXITO! Actualizada columna idioma\n";
            } else {
                echo "   ⚠️  No se pudo actualizar idioma: " . json_encode($updateResponse3) . "\n";
            }
            
            return true;
        } else {
            echo "   ❌ Error también con status solas: " . json_encode($response2) . "\n";
            return false;
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
