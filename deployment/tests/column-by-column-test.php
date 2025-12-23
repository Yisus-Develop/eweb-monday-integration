<?php
// column-by-column-test.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- DIAGNÓSTICO COLUMNA POR COLUMNA ---\n";

$baseColumns = [
    NewColumnIds::LEAD_EMAIL => ['email' => 'test@example.com', 'text' => 'test@example.com'],
    NewColumnIds::NUMERIC_SCORE => 5,
    NewColumnIds::COUNTRY_TEXT => 'México',
    NewColumnIds::DATE_CREATED => ['date' => date('Y-m-d')],
    NewColumnIds::RAW_DATA_JSON => 'Simple Text Test'
];

foreach ($baseColumns as $key => $val) {
    try {
        echo "Probando solo columna '$key'... ";
        $monday->createItem($boardId, "Test $key", [$key => $val]);
        echo "✅ Éxito\n";
    } catch (Exception $e) {
        echo "❌ Fallo: " . $e->getMessage() . "\n";
        if (file_exists(__DIR__ . '/last_error.json')) {
            echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
        }
    }
}
?>
