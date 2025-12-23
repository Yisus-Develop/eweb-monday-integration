<?php
// cleanup-columns.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- LISTANDO Y LIMPIANDO COLUMNAS ---\n";

$response = $monday->query('query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        columns {
            id
            title
            type
        }
    }
}', ['boardId' => (int)$boardId]);

$columns = $response['boards'][0]['columns'] ?? [];
$toDelete = [];
$keepRaw = null;
$keepType = null;

foreach ($columns as $col) {
    echo "ID: {$col['id']} | Title: {$col['title']} | Type: {$col['type']}\n";
    
    // Identificar duplicados de Datos Formulario
    if (strpos($col['title'], 'Datos Formulario') !== false || strpos($col['title'], 'Respaldo RAW') !== false) {
        if (!$keepRaw) {
            $keepRaw = $col['id'];
            echo ">> MANTENIENDO: {$col['title']} ({$col['id']})\n";
        } else {
            $toDelete[] = $col['id'];
        }
    }
    
    // Identificar duplicados de Tipo de Lead
    if (strpos($col['title'], 'Tipo de Lead') !== false) {
        if ($col['title'] === 'Tipo de Lead V2' && !$keepType) {
            $keepType = $col['id'];
            echo ">> MANTENIENDO: {$col['title']} ({$col['id']})\n";
        } elseif ($col['id'] !== 'type_of_lead') { // No borrar la original del sistema si existe
             $toDelete[] = $col['id'];
        }
    }
}

echo "\n--- ELIMINANDO DUPLICADOS ---\n";
foreach ($toDelete as $id) {
    try {
        $monday->deleteColumn($boardId, $id);
        echo "✅ Borrada: $id\n";
    } catch (Exception $e) {
        echo "❌ No se pudo borrar $id: " . $e->getMessage() . "\n";
    }
}

echo "\n--- FINALIZADO. KeepRaw: $keepRaw | KeepType: $keepType ---\n";
?>
