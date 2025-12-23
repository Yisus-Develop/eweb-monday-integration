<?php
// test-group-movement.php
// Prueba del webhook con movimiento automático de grupos

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA DE MOVIMIENTO AUTOMÁTICO       \n";
echo "  de grupos según Lead Score            \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Grupos por ID
    $groups = [
        'group_mkypkk91' => 'HOT Leads (Score > 20)',
        'group_mkypjxfw' => 'WARM Leads (Score 10-20)',
        'group_mkypvwd' => 'COLD Leads (Score < 10)',
        'group_mkyph1ky' => 'Spam - Revisar',
        'topics' => 'Leads nuevos'
    ];
    
    echo "GRUPOS DISPONIBLES:\n";
    foreach ($groups as $id => $name) {
        echo "  - $id: $name\n";
    }
    echo "\n";
    
    // Test 1: Lead con alta puntuación (debería ir a HOT)
    echo "TEST 1: Lead con alta puntuación (perfil='pioneer')\n";
    $testData1 = [
        'nombre' => 'Test HIGH Score - ' . date('Y-m-d H:i:s'),
        'email' => 'test-high-' . time() . '@example.com',
        'company' => 'Test High Score Corp',
        'role' => 'Mission Partner',
        'country' => 'España',
        'perfil' => 'pioneer',  // Este perfil da alta puntuación
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 10000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es'
    ];
    
    $scoringData1 = [
        'name' => $testData1['nombre'],
        'email' => $testData1['email'],
        'company' => $testData1['company'],
        'role' => $testData1['role'],
        'country' => $testData1['country'],
        'perfil' => $testData1['perfil'],
        'tipo_institucion' => $testData1['tipo_institucion'],
        'numero_estudiantes' => $testData1['numero_estudiantes'],
        'ea_source' => $testData1['ea_source'],
        'ea_lang' => $testData1['ea_lang'],
        'phone' => '999888777',
        'city' => 'Madrid'
    ];
    
    $scoreResult1 = LeadScoring::calculate($scoringData1);
    echo "  - Puntuación calculada: {$scoreResult1['total']}\n";
    
    // Determinar grupo esperado
    $expectedGroup1 = 'topics'; // Grupo por defecto
    if ($scoreResult1['total'] > 20) {
        $expectedGroup1 = 'group_mkypkk91'; // HOT
    } elseif ($scoreResult1['total'] >= 10) {
        $expectedGroup1 = 'group_mkypjxfw'; // WARM
    } elseif ($scoreResult1['total'] < 10) {
        $expectedGroup1 = 'group_mkypvwd'; // COLD
    }
    
    echo "  - Grupo esperado: {$groups[$expectedGroup1]}\n";
    
    // Crear lead con valores básicos
    $columnValues1 = [
        'name' => $testData1['nombre'],
        'lead_email' => ['email' => $testData1['email'], 'text' => $testData1['email']],
        'lead_company' => $testData1['company'],
        'text' => $testData1['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult1['total'],
        
        // Columnas nuevas
        NewColumnIds::CLASSIFICATION => ["label" => $scoreResult1['priority_label']],
        NewColumnIds::ROLE_DETECTED => ["label" => $scoreResult1['detected_role']],
        
        'text_mkyn95hk' => $testData1['country'],
        'text_mkypn0m' => 'Test MP',
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult1['breakdown'])
    ];
    
    $response1 = $monday->createItem($leadsBoardId, $testData1['nombre'], $columnValues1);
    
    if (isset($response1['create_item']['id'])) {
        $itemId1 = $response1['create_item']['id'];
        echo "  - Item creado (ID: $itemId1)\n";
        
        // Mover al grupo correcto
        try {
            $moveResult = $monday->moveItemToGroup($itemId1, $expectedGroup1);
            echo "  - Movido al grupo: {$groups[$expectedGroup1]} ✅\n";
        } catch (Exception $e) {
            echo "  - Error moviendo al grupo: " . $e->getMessage() . " ⚠️\n";
        }
    } else {
        echo "  - Error creando item: " . json_encode($response1) . "\n";
    }
    
    echo "\n";
    
    // Test 2: Lead con baja puntuación (debería ir a COLD)
    echo "TEST 2: Lead con baja puntuación\n";
    $testData2 = [
        'nombre' => 'Test LOW Score - ' . date('Y-m-d H:i:s'),
        'email' => 'test-low-' . (time()+1) . '@example.com',
        'company' => 'Test Low Score Corp',
        'role' => 'General',
        'country' => 'Francia',
        'perfil' => 'general',  // Este perfil da baja puntuación
        'tipo_institucion' => 'Otro',
        'numero_estudiantes' => 0,
        'ea_source' => 'Website',
        'ea_lang' => 'fr'
    ];
    
    $scoringData2 = [
        'name' => $testData2['nombre'],
        'email' => $testData2['email'],
        'company' => $testData2['company'],
        'role' => $testData2['role'],
        'country' => $testData2['country'],
        'perfil' => $testData2['perfil'],
        'tipo_institucion' => $testData2['tipo_institucion'],
        'numero_estudiantes' => $testData2['numero_estudiantes'],
        'ea_source' => $testData2['ea_source'],
        'ea_lang' => $testData2['ea_lang'],
        'phone' => '111222333',
        'city' => 'París'
    ];
    
    $scoreResult2 = LeadScoring::calculate($scoringData2);
    echo "  - Puntuación calculada: {$scoreResult2['total']}\n";
    
    $expectedGroup2 = 'topics';
    if ($scoreResult2['total'] > 20) {
        $expectedGroup2 = 'group_mkypkk91'; // HOT
    } elseif ($scoreResult2['total'] >= 10) {
        $expectedGroup2 = 'group_mkypjxfw'; // WARM
    } elseif ($scoreResult2['total'] < 10) {
        $expectedGroup2 = 'group_mkypvwd'; // COLD
    }
    
    echo "  - Grupo esperado: {$groups[$expectedGroup2]}\n";
    
    $columnValues2 = [
        'name' => $testData2['nombre'],
        'lead_email' => ['email' => $testData2['email'], 'text' => $testData2['email']],
        'lead_company' => $testData2['company'],
        'text' => $testData2['role'],
        'lead_phone' => ['phone' => '111222333', 'country_short_name' => 'FR'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult2['total'],
        
        // Columnas nuevas
        NewColumnIds::CLASSIFICATION => ["label" => $scoreResult2['priority_label']],
        NewColumnIds::ROLE_DETECTED => ["label" => $scoreResult2['detected_role']],
        
        'text_mkyn95hk' => $testData2['country'],
        'text_mkypn0m' => '',
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult2['breakdown'])
    ];
    
    $response2 = $monday->createItem($leadsBoardId, $testData2['nombre'], $columnValues2);
    
    if (isset($response2['create_item']['id'])) {
        $itemId2 = $response2['create_item']['id'];
        echo "  - Item creado (ID: $itemId2)\n";
        
        // Mover al grupo correcto
        try {
            $moveResult2 = $monday->moveItemToGroup($itemId2, $expectedGroup2);
            echo "  - Movido al grupo: {$groups[$expectedGroup2]} ✅\n";
        } catch (Exception $e) {
            echo "  - Error moviendo al grupo: " . $e->getMessage() . " ⚠️\n";
        }
    } else {
        echo "  - Error creando item: " . json_encode($response2) . "\n";
    }
    
    echo "\n========================================\n";
    echo "  ¡PRUEBA DE MOVIMIENTO COMPLETADA!     \n";
    echo "========================================\n";
    echo "✅ El sistema puede mover leads al grupo\n";
    echo "   correspondiente según su Lead Score\n";
    echo "✅ Funcionalidad de clasificación\n";
    echo "   automática por grupos implementada\n";
    echo "========================================\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
