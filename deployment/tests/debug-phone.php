<?php
// debug-phone.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- DIAGNÓSTICO DE TELÉFONO ---\n";

// Escenario 1: Objeto Nativo
try {
    echo "Probando Objeto Nativo...\n";
    $monday->createItem($boardId, 'Phone Object', [
        NewColumnIds::LEAD_PHONE => ['phone' => '123456789', 'country_short_name' => 'MX']
    ]);
    echo "✅ Éxito con Objeto Nativo\n";
} catch (Exception $e) {
    echo "❌ Fallo Objeto: " . $e->getMessage() . "\n";
}

// Escenario 2: Stringified JSON
try {
    echo "Probando Stringified JSON...\n";
    $monday->createItem($boardId, 'Phone String', [
        NewColumnIds::LEAD_PHONE => json_encode(['phone' => '987654321', 'country_short_name' => 'CO'])
    ]);
    echo "✅ Éxito con Stringified JSON\n";
} catch (Exception $e) {
    echo "❌ Fallo String: " . $e->getMessage() . "\n";
}
?>
