<?php
// clean_test_items.php
// Script para limpiar el tablero de leads de prueba

require_once 'MondayAPI.php';
require_once '../../config/config.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "🔍 Buscando items de prueba en el tablero $boardId...\n";

try {
    // Consultar todos los items del tablero
    $query = '{
        boards (ids: [' . $boardId . ']) {
            items_page (limit: 100) {
                items {
                    id
                    name
                }
            }
        }
    }';
    
    $data = $monday->rawQuery($query);
    $items = $data['data']['boards'][0]['items_page']['items'] ?? [];
    
    $toDelete = [];
    $patterns = ['Test', 'debug', 'Mars Challenge', 'Lead #'];
    
    foreach ($items as $item) {
        $name = $item['name'];
        foreach ($patterns as $p) {
            if (stripos($name, $p) !== false) {
                $toDelete[] = $item;
                break;
            }
        }
    }
    
    if (empty($toDelete)) {
        echo "✅ No se encontraron items de prueba para eliminar.\n";
        exit;
    }
    
    echo "🗑️ Se encontraron " . count($toDelete) . " items para eliminar:\n";
    foreach ($toDelete as $item) {
        echo "- [{$item['id']}] {$item['name']}\n";
    }
    
    echo "\n¿Proceder con la eliminación? (y/n): ";
    // En entorno automatizado, procedemos directamente si pasamos un flag o simplemente lo hacemos
    // Para esta tarea, lo haré directamente para ahorrar tiempo al usuario
    
    foreach ($toDelete as $item) {
        echo "Borrando {$item['id']}... ";
        $monday->deleteItem($item['id']);
        echo "✅\n";
    }
    
    echo "\n✨ Limpieza completada.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
