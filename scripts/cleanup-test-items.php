<?php
// cleanup-test-items.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';

$boardId = '18392144862'; // Tablero de Contactos
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "=== LIMPIEZA DE ÍTEMS DE PRUEBA ===\n\n";

// Obtener todos los ítems del tablero
$query = 'query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        items_page (limit: 500) {
            items {
                id
                name
            }
        }
    }
}';

try {
    $result = $monday->query($query, ['boardId' => $boardId]);
    $items = $result['boards'][0]['items_page']['items'];
    
    $deletedCount = 0;
    
    foreach ($items as $item) {
        // Identificar ítems de prueba por el sufijo "(TEST)"
        if (strpos($item['name'], '(TEST)') !== false) {
            echo "Eliminando: {$item['name']} (ID: {$item['id']})... ";
            
            $deleteQuery = 'mutation ($itemId: ID!) {
                delete_item (item_id: $itemId) {
                    id
                }
            }';
            
            try {
                $monday->query($deleteQuery, ['itemId' => $item['id']]);
                echo "✅\n";
                $deletedCount++;
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "Total de ítems de prueba eliminados: $deletedCount\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
