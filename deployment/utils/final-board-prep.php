<?php
// final-board-prep.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- LIMPIEZA FINAL Y AJUSTE DE ETIQUETAS ---\n";

// 1. Eliminar columnas redundantes
$idsToDelete = ['long_text_mkywtqvt', 'long_text_mkyweh1w', 'long_text_mkyw145w'];
foreach ($idsToDelete as $id) {
    try {
        $monday->deleteColumn($boardId, $id);
        echo "✅ Eliminada columna redundante: $id\n";
    } catch (Exception $e) {
        // Ignorar si no existe
    }
}

// 2. Ajustar etiquetas de Tipo de Lead V2 (Terminología Directa)
// Queremos: Institución, Ciudad, Empresa, Pioneer, Mentor, País, Zer, Otros
$labels = ["Institución", "Ciudad", "Empresa", "Pioneer", "Mentor", "País", "Zer", "Otros"];
$settings = json_encode(['labels' => array_map(function($l) { return ['name' => $l]; }, $labels)]);

try {
    $monday->query('mutation ($boardId: ID!, $columnId: String!, $value: JSON!) {
        change_column_metadata (board_id: $boardId, column_id: $columnId, column_property: settings, value: $value) {
            id
        }
    }', [
        'boardId' => (int)$boardId,
        'columnId' => NewColumnIds::TYPE_OF_LEAD,
        'value' => $settings
    ]);
    echo "✅ Etiquetas de 'Tipo de Lead V2' actualizadas a terminology directa.\n";
} catch (Exception $e) {
    echo "❌ Error actualizando etiquetas: " . $e->getMessage() . "\n";
}

echo "--- FINALIZADO ---\n";
?>
