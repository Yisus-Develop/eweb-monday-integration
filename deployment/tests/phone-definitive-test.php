<?php
// phone-definitive-test.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- PRUEBA DEFINITIVA DE TELÉFONO ---\n";

// La hipótesis es que Monday quiere el VALOR de la columna como un string JSON
// aunque la variable global sea un JSON object.

try {
    echo "Probando: Valor como string JSON...\n";
    $columnValues = [
        NewColumnIds::LEAD_PHONE => json_encode([
            'phone' => '123456789', 
            'country_short_name' => 'MX'
        ])
    ];
    $monday->createItem($boardId, 'Test Phone String-Value', $columnValues);
    echo "✅ Éxito con Valor-String!\n";
} catch (Exception $e) {
    echo "❌ Falló: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "DETALLE: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
