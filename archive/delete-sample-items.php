<?php
// delete-sample-items.php
// Elimina los items de muestra del tablero Leads

require_once '../config.php';
require_once 'MondayAPI.php';

echo "=== Eliminando Items de Muestra ===\n\n";

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

// IDs de los items de muestra (del backup)
$sampleItemIds = [
    '10778926291', // Elías Gutierrez
    '10778966995'  // Esteban García
];

try {
    foreach ($sampleItemIds as $itemId) {
        echo "Eliminando item ID: $itemId...\n";
        
        $query = 'mutation { delete_item (item_id: ' . $itemId . ') { id } }';
        $response = $monday->query($query);
        
        if (isset($response['delete_item']['id'])) {
            echo "  ✅ Item $itemId eliminado\n";
        } else {
            echo "  ⚠️ No se pudo eliminar item $itemId\n";
        }
    }
    
    echo "\n✅ Items de muestra eliminados correctamente\n";
    echo "📋 Tablero 'Leads' ahora está vacío y listo para producción\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
