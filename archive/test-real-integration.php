<?php
// test-real-integration.php
// Pruebas reales de integración con Monday.com

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/NewColumnIds.php';
require_once __DIR__ . '/StatusConstants.php';

echo "=== TEST DE INTEGRACIÓN REAL ===\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;
    
    // Simular datos de CF7
    $testData = [
        'your-name' => 'Test Robot ' . date('His'),
        'your-email' => 'test_robot_' . date('His') . '@example.com',
        'your-phone' => '+34123456789',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'pais_cf7' => 'España',
        'ea_city' => 'Madrid'
    ];

    echo "1. PROCESANDO LEAD LOCALMENTE...\n";
    $scoringData = [
        'name' => $testData['your-name'],
        'email' => $testData['your-email'],
        'phone' => $testData['your-phone'],
        'company' => 'Empresa de Prueba',
        'role' => $testData['tipo_institucion'],
        'country' => $testData['pais_cf7'],
        'city' => $testData['ea_city'],
        'perfil' => $testData['perfil'],
        'tipo_institucion' => $testData['tipo_institucion'],
    ];

    $scoreResult = LeadScoring::calculate($scoringData);
    echo "   Score: " . $scoreResult['total'] . " (" . $scoreResult['priority_label'] . ")\n";

    // Mapeo a etiquetas reales (usando StatusConstants como puente)
    $labelToUse = StatusConstants::CLASSIFICATION_HOT; // Forzamos para el test si el score es alto
    if ($scoreResult['priority_label'] === 'WARM') $labelToUse = StatusConstants::CLASSIFICATION_WARM;
    if ($scoreResult['priority_label'] === 'COLD') $labelToUse = StatusConstants::CLASSIFICATION_COLD;

    echo "   Etiqueta a usar en Monday: $labelToUse\n\n";

    echo "2. ENVIANDO LEAD COMPLETO A MONDAY.COM...\n";
    
    $columnValues = [
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        'classification_status' => ['label' => $labelToUse],
        'role_detected_new' => ['label' => 'Rector/Director'],
        'text' => 'Robot Tester', // Puesto
        'text_mkyn95hk' => $scoringData['country']
    ];

    $response = $monday->createItem($boardId, $scoringData['name'] . " (FINAL TEST)", $columnValues);
    
    if (isset($response['create_item']['id'])) {
        echo "✅ ITEM CREADO EXITOSAMENTE: " . $response['create_item']['id'] . "\n";
        echo "Nombre: " . $scoringData['name'] . " (FINAL TEST)\n";
        echo "Puedes verlo ya en tu tablero de Monday.com.\n";
    } else {
        echo "❌ FALLO FINAL\n";
        file_put_contents('test_result.log', json_encode($response, JSON_PRETTY_PRINT));
    }

} catch (Exception $e) {
    echo "❌ ERROR DETECTADO (ver error_diagnostic.log)\n";
    file_put_contents('error_diagnostic.log', $e->getMessage());
}
