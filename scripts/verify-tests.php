<?php
// verify-tests.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../MondayAPI.php';
require_once __DIR__ . '/../NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== VERIFICANDO ÍTEMS DE PRUEBA EN MONDAY ===\n\n";

$query = 'query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        items_page (limit: 10) {
            items {
                id
                name
                column_values {
                    id
                    text
                }
            }
        }
    }
}';

try {
    $result = $monday->query($query, ['boardId' => $boardId]);
    $items = $result['boards'][0]['items_page']['items'];

    foreach ($items as $item) {
        if (strpos($item['name'], '(TEST)') !== false) {
            echo "📍 Ítem: {$item['name']} (ID: {$item['id']})\n";
            foreach ($item['column_values'] as $cv) {
                // Mostrar solo columnas clave para no saturar
                if (in_array($cv['id'], [
                    NewColumnIds::CLASSIFICATION, 
                    NewColumnIds::SOURCE_CHANNEL, 
                    NewColumnIds::AMOUNT,
                    NewColumnIds::CITY,
                    NewColumnIds::COMMENTS
                ])) {
                    echo "   - {$cv['id']}: {$cv['text']}\n";
                }
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
