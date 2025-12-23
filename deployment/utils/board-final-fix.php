<?php
// board-final-fix.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- LIMPIEZA Y AJUSTE DE ETIQUETAS --- \n";

// 1. Columnas a eliminar (duplicados de pruebas anteriores)
$duplicatedIds = ['long_text_mkywtqvt', 'long_text_mkyweh1w', 'long_text_mkyw145w', 'long_text_mkypqppc'];
foreach ($duplicatedIds as $id) {
    echo "Eliminando $id... ";
    try {
        $monday->deleteColumn($boardId, $id);
        echo "✅\n";
    } catch (Exception $e) {
        echo "❌ (Omitida o no existe)\n";
    }
}

// 2. Ajustar "Tipo de Lead V2" con terminology directa
$labels = ["Zer", "Pioneer", "Institución", "Ciudad", "Empresa", "Mentor", "País", "Otros"];
$settings = json_encode(['labels' => array_map(function($l) { return ['name' => $l]; }, $labels)]);

try {
    // Usamos change_column_metadata para inyectar la lista de etiquetas
    $monday->query('mutation ($boardId: ID!, $columnId: String!, $value: JSON!) {
        change_column_metadata (board_id: $boardId, column_id: $columnId, column_property: settings, value: $value) {
            id
        }
    }', [
        'boardId' => (int)$boardId,
        'columnId' => 'dropdown_mkywgchz',
        'value' => $settings
    ]);
    echo "✅ Etiquetas actualizadas en 'Tipo de Lead V2'.\n";
} catch (Exception $e) {
    echo "❌ Error etiquetas: " . $e->getMessage() . "\n";
}

echo "--- TODO LIMPIO Y CONFIGURADO ---\n";
?>
