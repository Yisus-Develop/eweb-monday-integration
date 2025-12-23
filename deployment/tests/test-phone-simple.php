<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA: TELÉFONO COMO STRING SIMPLE ---\n";

try {
    // 1. Crear item básico
    $res = $monday->createItem($boardId, 'Test Simple Phone');
    $itemId = $res['create_item']['id'];
    echo "Item creado: $itemId\n";

    // 2. Probar cambio simple (sin JSON, solo texto)
    echo "Probando change_simple_column_value con '+525512345678'...\n";
    $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LEAD_PHONE, '+525512345678');
    echo "✅ ÉXITO CON STRING SIMPLE!\n";

} catch (Exception $e) {
    echo "❌ FALLO: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
