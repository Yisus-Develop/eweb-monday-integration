<?php
// isolate-column-error.php
// Script para identificar exactamente qué columna está fallando en Monday

require_once 'deployment/NewColumnIds.php';
require_once 'deployment/StatusConstants.php';
require_once 'deployment/MondayAPI.php';
require_once '../../config/config.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$testEmail = 'debug' . time() . '@test.com';
$columnsToTest = [
    'Email' => [NewColumnIds::EMAIL => ['email' => $testEmail, 'text' => $testEmail]],
    'Phone' => [NewColumnIds::PHONE => ['phone' => '123456789', 'countryShortName' => 'CO']],
    'Puesto' => [NewColumnIds::PUESTO => 'Tester'],
    'Status' => [NewColumnIds::STATUS => ['label' => StatusConstants::STATUS_LEAD]],
    'Score' => [NewColumnIds::LEAD_SCORE => 10],
    'Classification' => [NewColumnIds::CLASSIFICATION => ['label' => 'COLD']],
    'Role' => [NewColumnIds::ROLE_DETECTED => ['label' => 'Joven']],
    'Country' => [NewColumnIds::COUNTRY => 'CO'],
    'City' => [NewColumnIds::CITY => 'Medellin'],
    'Entity' => [NewColumnIds::ENTITY_TYPE => ['label' => 'Colegio']],
    'IA' => [NewColumnIds::IA_ANALYSIS => ['text' => 'Test']],
];

echo "🛠️ Probando cada columna individualmente...\n\n";

foreach ($columnsToTest as $name => $val) {
    try {
        echo "Prueba: $name... ";
        $res = $monday->createItem($boardId, "Test $name", $val);
        echo "✅ ÉXITO (ID: " . ($res['create_item']['id'] ?? 'N/A') . ")\n";
    } catch (Exception $e) {
        echo "❌ FALLÓ\n";
        echo "   Mensaje: " . $e->getMessage() . "\n";
        
        // Intentar obtener más info si es error de Monday
        if (strpos($e->getMessage(), 'Error Monday API') !== false) {
             echo "   🔍 RAW ERROR: " . $e->getMessage() . "\n";
        }
    }
}
