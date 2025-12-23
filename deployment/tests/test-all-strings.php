<?php
// test-all-strings.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA: TODO COMO STRINGS ---\n";

$columnValues = [
    NewColumnIds::LEAD_EMAIL => json_encode(['email' => 'test@strings.com', 'text' => 'Test Strings']),
    NewColumnIds::NUMERIC_SCORE => "15", // Como String
    NewColumnIds::COUNTRY_TEXT => "México",
    NewColumnIds::DATE_CREATED => json_encode(['date' => date('Y-m-d')]),
    NewColumnIds::RAW_DATA_JSON => "Respaldo directo"
];

try {
    echo "Probando 'Pure Strings'...\n";
    $monday->createItem($boardId, 'Test All Strings', $columnValues);
    echo "✅ ÉXITO TOTAL!\n";
} catch (Exception $e) {
    echo "❌ FALLO: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
