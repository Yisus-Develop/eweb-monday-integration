<?php
// update-dropdown-labels.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- ACTUALIZACIÓN DE ETIQUETAS 'Tipo de Lead V2' ---\n\n";

// Las etiquetas deben coincidir exactamente con los perfiles del formulario
$labels = [
    'Zer',
    'Pioneer',
    'Institución',
    'Ciudad',
    'Empresa',
    'Mentor',
    'País',
    'General',
    'Otro'
];

echo "Etiquetas a configurar:\n";
foreach ($labels as $label) {
    echo "  - $label\n";
}

try {
    // Actualizar las etiquetas del dropdown
    $settings = json_encode([
        'labels' => array_map(function($l) { 
            return ['name' => $l]; 
        }, $labels)
    ]);
    
    $query = 'mutation ($boardId: ID!, $columnId: String!, $value: JSON!) {
        change_column_metadata (
            board_id: $boardId, 
            column_id: $columnId, 
            column_property: "settings", 
            value: $value
        ) {
            id
        }
    }';
    
    $monday->query($query, [
        'boardId' => (int)$boardId,
        'columnId' => 'dropdown_mkywgchz',
        'value' => $settings
    ]);
    
    echo "\n✅ Etiquetas actualizadas correctamente en 'Tipo de Lead V2'.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (file_exists(__DIR__ . '/last_error.json')) {
        echo "Detalles: " . file_get_contents(__DIR__ . '/last_error.json') . "\n";
    }
}
?>
