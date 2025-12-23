<?php
// cleanup-test-items.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- LIMPIEZA DE ITEMS DE PRUEBA ---\n\n";

// Obtener todos los items del tablero
$query = 'query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        items_page (limit: 100) {
            items {
                id
                name
            }
        }
    }
}';

try {
    $data = $monday->query($query, ['boardId' => (int)$boardId]);
    $items = $data['boards'][0]['items_page']['items'] ?? [];
    
    $testKeywords = ['Test', 'test', 'Prueba', 'prueba', 'Global', 'Simple', 'Minimal', 'Hybrid', 'String', 'Fix', 'Robusto'];
    
    $toDelete = [];
    foreach ($items as $item) {
        foreach ($testKeywords as $keyword) {
            if (stripos($item['name'], $keyword) !== false) {
                $toDelete[] = $item;
                break;
            }
        }
    }
    
    echo "Encontrados " . count($toDelete) . " items de prueba para eliminar:\n";
    foreach ($toDelete as $item) {
        echo "  - ID {$item['id']}: {$item['name']}\n";
    }
    
    if (count($toDelete) > 0) {
        echo "\n¿Proceder con la eliminación? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) === 'y') {
            foreach ($toDelete as $item) {
                $deleteQuery = 'mutation ($itemId: ID!) {
                    delete_item (item_id: $itemId) {
                        id
                    }
                }';
                $monday->query($deleteQuery, ['itemId' => (int)$item['id']]);
                echo "✅ Eliminado: {$item['name']}\n";
            }
            echo "\n--- LIMPIEZA COMPLETADA ---\n";
        } else {
            echo "Cancelado.\n";
        }
    } else {
        echo "No hay items de prueba para eliminar.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
