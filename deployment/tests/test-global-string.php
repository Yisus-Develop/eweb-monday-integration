<?php
// test-global-string.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA: GLOBAL STRING ---\n";

try {
    echo "Probando stringificar TODO el bloque columnValues...\n";
    $values = [
        'text_mkyn95hk' => "México",
        'lead_email' => ['email' => 'global@test.com', 'text' => 'Global Text'],
        'lead_phone' => ['phone' => '123456789', 'country_short_name' => 'MX']
    ];
    $monday->createItem($boardId, 'Complex Global Test', json_encode($values));
    echo "✅ ÉXITO GLOBAL STRING!\n";
} catch (Exception $e) {
    echo "❌ FALLO: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
