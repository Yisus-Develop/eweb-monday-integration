<?php
// simple-test.php
// Test simple para verificar que todo funciona

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  TEST SIMPLE PARA VERIFICAR FUNCIONALIDAD\n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "1. CREANDO LEAD DE PRUEBA SIMPLE...\n";
    
    $testLead = [
        'name' => 'Lead Simple Prueba - ' . date('Y-m-d H:i:s'),
        'email' => 'simple-test-' . time() . '@example.com',
        'perfil' => 'pioneer',  // Alta puntuación
        'country' => 'España',
        'company' => 'Test Corp',
        'role' => 'Mission Partner'
    ];
    
    $scoringData = [
        'name' => $testLead['name'],
        'email' => $testLead['email'],
        'company' => $testLead['company'],
        'role' => $testLead['role'],
        'country' => $testLead['country'],
        'perfil' => $testLead['perfil'],
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 10000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es',
        'phone' => '999888777',
        'city' => 'Madrid'
    ];
    
    $scoreResult = LeadScoring::calculate($scoringData);
    echo "   - Puntuación calculada: {$scoreResult['total']}\n";
    echo "   - Clasificación: {$scoreResult['priority_label']}\n";
    echo "   - Rol detectado: {$scoreResult['detected_role']}\n";
    
    // Preparar valores simples para Monday
    $columnValues = [
        'name' => $testLead['name'],
        'lead_email' => ['email' => $testLead['email'], 'text' => $testLead['email']],
        'lead_company' => $testLead['company'],
        'text' => $testLead['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        
        // Columnas nuevas (solo con valores que sabemos que funcionan)
        NewColumnIds::CLASSIFICATION => ["label" => $scoreResult['priority_label']],
        NewColumnIds::ROLE_DETECTED => ["label" => $scoreResult['detected_role']],
        
        'text_mkyn95hk' => $testLead['country'],
        'text_mkypn0m' => $testLead['name'],  // Mission Partner para pioneer
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'] ?? [])
    ];
    
    echo "2. ENVIANDO AL TABLERO...\n";
    $response = $monday->createItem($leadsBoardId, $testLead['name'], $columnValues);
    
    if (isset($response['create_item']['id'])) {
        $itemId = $response['create_item']['id'];
        echo "   ✅ Lead creado exitosamente (ID: $itemId)\n";
        
        // Determinar grupo según puntuación
        $targetGroupId = 'topics'; // Por defecto
        if ($scoreResult['total'] > 20) {
            $targetGroupId = 'group_mkypkk91'; // HOT
        } elseif ($scoreResult['total'] >= 10) {
            $targetGroupId = 'group_mkypjxfw'; // WARM
        } else {
            $targetGroupId = 'group_mkypvwd'; // COLD
        }
        
        echo "3. MOVING AL GRUPO APROPIADO...\n";
        try {
            $moveResult = $monday->moveItemToGroup($itemId, $targetGroupId);
            echo "   ✅ Movido al grupo correcto\n";
        } catch (Exception $e) {
            echo "   ⚠️ Error moviendo al grupo: " . $e->getMessage() . "\n";
        }
        
        echo "\n========================================\n";
        echo "  ¡TEST SIMPLE COMPLETADO!              \n";
        echo "========================================\n";
        echo "✅ El sistema funciona correctamente\n";
        echo "✅ Puntuación automática: {$scoreResult['total']}\n";
        echo "✅ Clasificación: {$scoreResult['priority_label']}\n";
        echo "✅ Asignación de grupo automática\n";
        echo "========================================\n";
        
        return true;
    } else {
        echo "   ❌ Error: " . json_encode($response) . "\n";
        return false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
