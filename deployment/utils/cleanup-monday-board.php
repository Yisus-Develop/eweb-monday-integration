<?php
// cleanup-monday-board.php - Limpieza completa de items de prueba
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== LIMPIEZA DE TABLERO MONDAY.COM ===\n\n";

// Palabras clave que identifican items de prueba
$testKeywords = [
    'Test', 'test', 'Prueba', 'prueba', 'Global', 'Simple', 
    'Minimal', 'Hybrid', 'String', 'Fix', 'Robusto', 'Ana', 
    'Carlos', 'Diana', 'Eduardo', 'Fernanda', 'Gabriel', 
    'Helena', 'Ignacio', 'Luis', 'Ingrid'
];

// Obtener TODOS los items del tablero
$query = 'query ($boardId: ID!, $limit: Int!) {
    boards (ids: [$boardId]) {
        items_page (limit: $limit) {
            items {
                id
                name
                group {
                    id
                    title
                }
            }
        }
    }
}';

try {
    echo "📋 Obteniendo items del tablero...\n";
    $data = $monday->query($query, ['boardId' => (int)$boardId, 'limit' => 500]);
    $items = $data['boards'][0]['items_page']['items'] ?? [];
    
    echo "Total de items en el tablero: " . count($items) . "\n\n";
    
    // Filtrar items de prueba
    $toDelete = [];
    foreach ($items as $item) {
        foreach ($testKeywords as $keyword) {
            if (stripos($item['name'], $keyword) !== false) {
                $toDelete[] = $item;
                break;
            }
        }
    }
    
    if (empty($toDelete)) {
        echo "✅ No se encontraron items de prueba para eliminar.\n";
        echo "El tablero está limpio.\n";
        exit(0);
    }
    
    echo "🗑️  Encontrados " . count($toDelete) . " items de prueba:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($toDelete as $item) {
        $group = $item['group']['title'] ?? 'Sin grupo';
        echo sprintf("  - [%s] %s (Grupo: %s)\n", $item['id'], $item['name'], $group);
    }
    
    echo str_repeat("=", 80) . "\n";
    echo "\n⚠️  ADVERTENCIA: Esto eliminará " . count($toDelete) . " items de Monday.com\n";
    echo "¿Deseas continuar? (escribe 'SI' para confirmar): ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtoupper($line) !== 'SI') {
        echo "\n❌ Operación cancelada.\n";
        exit(0);
    }
    
    echo "\n🚀 Iniciando eliminación...\n\n";
    
    $deleteQuery = 'mutation ($itemId: ID!) {
        delete_item (item_id: $itemId) {
            id
        }
    }';
    
    $deleted = 0;
    $failed = 0;
    
    foreach ($toDelete as $item) {
        try {
            $monday->query($deleteQuery, ['itemId' => (int)$item['id']]);
            echo "  ✅ Eliminado: {$item['name']}\n";
            $deleted++;
            usleep(200000); // 200ms delay para no saturar la API
        } catch (Exception $e) {
            echo "  ❌ Error eliminando {$item['name']}: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "📊 RESUMEN:\n";
    echo "  ✅ Eliminados exitosamente: $deleted\n";
    if ($failed > 0) {
        echo "  ❌ Fallidos: $failed\n";
    }
    echo "\n✨ Limpieza completada. El tablero ahora solo tiene datos reales.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
