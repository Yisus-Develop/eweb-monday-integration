<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$id = 10839769933;

echo "--- VERIFICACIÓN FINAL ITEM $id ---\n\n";

$query = 'query ($id: [ID!]) {
    items (ids: $id) {
        id
        name
        column_values {
            id
            text
        }
    }
}';

try {
    $data = $monday->query($query, ['id' => [(int)$id]]);
    $item = $data['items'][0] ?? null;

    if (!$item) die("Item no encontrado.");

    echo "Nombre: {$item['name']}\n";
    foreach ($item['column_values'] as $val) {
        if (!empty($val['text'])) {
            echo "✅ {$val['id']}: {$val['text']}\n";
        } else {
            echo "❌ {$val['id']}: [VACÍO]\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
