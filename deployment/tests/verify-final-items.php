<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$itemIds = [10834283623, 10834283648, 10839764661, 10839757336]; // IDs de los últimos tests

echo "--- VERIFICACIÓN FINAL DE ITEMS ESPECÍFICOS ---\n\n";

$query = 'query ($ids: [ID!]) {
    items (ids: $ids) {
        id
        name
        column_values {
            id
            text
        }
    }
}';

try {
    $data = $monday->query($query, ['ids' => $itemIds]);
    foreach ($data['items'] as $item) {
        echo "ID: {$item['id']} | Nombre: {$item['name']}\n";
        foreach ($item['column_values'] as $val) {
            echo "   -> {$val['id']}: " . ($val['text'] ?: "[VACÍO]") . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
