<?php
// cleanup-board.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== LIMPIEZA TOTAL DEL TABLERO ===\n";

try {
    // 1. Obtener todos los items
    $query = 'query { boards (ids: ' . $boardId . ') { items_page (limit: 100) { items { id name } } } }';
    $result = $monday->query($query);
    $items = $result['boards'][0]['items_page']['items'] ?? [];

    if (empty($items)) {
        echo "No hay items para borrar.\n";
        exit;
    }

    echo "Borrando " . count($items) . " items...\n";

    foreach ($items as $item) {
        echo "Borrando: " . $item['name'] . " (ID: " . $item['id'] . ")... ";
        $delQuery = 'mutation { delete_item (item_id: ' . $item['id'] . ') { id } }';
        $monday->query($delQuery);
        echo "✅\n";
    }

    echo "=== LIMPIEZA COMPLETADA ===\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
