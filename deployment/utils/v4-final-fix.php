<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA DEFINITIVA ESTRUCTURA JSON ---\n";

$scenarios = [
    'Native Objects' => [
        'lead_email' => ['email' => 'native@test.com', 'text' => 'native@test.com'],
        'lead_phone' => ['phone' => '123456789', 'country_short_name' => 'MX'],
        'long_text_mkyw5ttg' => 'Native Object Text' // Direct string for long_text
    ]
];

foreach ($scenarios as $name => $values) {
    echo "Probando escenario: $name...\n";
    try {
        $monday->createItem($boardId, "Test $name", $values);
        echo "✅ EXITOS EN $name\n";
    } catch (Exception $e) {
        echo "❌ FALLO EN $name\n";
        $realError = file_exists(__DIR__ . '/last_error.json') ? file_get_contents(__DIR__ . '/last_error.json') : $e->getMessage();
        echo "Error Real: " . $realError . "\n";
    }
}
?>
