<?php
// test-minimal.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA MINIMALISTA ---\n";

try {
    echo "Probando 'País' (Text Column)...\n";
    $columnValues = [
        'text_mkyn95hk' => "México"
    ];
    $monday->createItem($boardId, 'Minimal Test', $columnValues);
    echo "✅ ÉXITO MINIMAL!\n";
} catch (Exception $e) {
    echo "❌ FALLO: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
