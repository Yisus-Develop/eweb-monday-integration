<?php
// stabilize-board.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- ESTABILIZACIÓN FINAL DEL TABLERO ---\n\n";

// 1. Obtener todas las columnas
$data = $monday->query('query ($id: ID!) { boards (ids: [$id]) { columns { id title type } } }', ['id' => (int)$boardId]);
$columns = $data['boards'][0]['columns'] ?? [];

$idsToKeep = [
    'name', 'subtasks_mkyp2wpq', 'lead_status', 'button', 'lead_email', 'lead_phone', 
    'numeric_mkyn2py0', 'text_mkyn95hk', 'date_mkypeap2', 'date_mkyp6w4t', 
    'classification_status', 'source_channel', 'language', 'role_detected_new', 
    'dropdown_mkywgchz'
];

$hasBackup = false;
$backupId = null;

echo "Análisis de columnas:\n";
foreach ($columns as $col) {
    if ($col['title'] === 'Respaldo RAW (JSON)') {
        $hasBackup = true;
        $backupId = $col['id'];
        echo "✅ Encontrado Respaldo: {$col['id']}\n";
    }
    
    // Borrar old 'type_of_lead' si existe
    if ($col['id'] === 'type_of_lead') {
        echo "🗑️ Borrando 'type_of_lead' (obsoleta)... ";
        try { $monday->deleteColumn($boardId, $col['id']); echo "OK\n"; } catch(Exception $e) { echo "Fail\n"; }
    }
}

// 2. Si no hay backup, crearlo
if (!$hasBackup) {
    echo "⚠️ Falta 'Respaldo RAW (JSON)'. Creándolo... ";
    try {
        $backupId = $monday->createColumn($boardId, 'Respaldo RAW (JSON)', 'long_text');
        echo "✅ Creado con ID: $backupId\n";
    } catch (Exception $e) {
        echo "❌ Error creando backup: " . $e->getMessage() . "\n";
    }
}

// 3. Verificar Terminology en Dropdown (Zer, Pioneer)
echo "\nVerificando etiquetas de 'Tipo de Lead V2' (dropdown_mkywgchz)...\n";
$labels = ["Zer", "Pioneer", "Institución", "Ciudad", "Empresa", "Mentor", "País", "Otros"];
$settings = json_encode(['labels' => array_map(function($l) { return ['name' => $l]; }, $labels)]);
try {
    $monday->query('mutation ($boardId: ID!, $columnId: String!, $value: JSON!) {
        change_column_metadata (board_id: $boardId, column_id: $columnId, column_property: settings, value: $value) {
            id
        }
    }', [
        'boardId' => (int)$boardId,
        'columnId' => 'dropdown_mkywgchz',
        'value' => $settings
    ]);
    echo "✅ Etiquetas OK.\n";
} catch (Exception $e) {
    echo "❌ Error etiquetas: " . $e->getMessage() . "\n";
}

echo "\n--- ESTABILIZACIÓN COMPLETADA ---\n";
echo "USA ESTE ID PARA RAW_DATA_JSON: $backupId\n";
?>
