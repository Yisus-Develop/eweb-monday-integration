<?php
// update-contacts-labels-v3.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Configurando etiquetas para el tablero de Contactos (v3 - Metadata)...\n";

function updateMetadata($monday, $boardId, $columnId, $labels) {
    echo "Actualizando columna: $columnId... ";
    
    $labelsJson = json_encode($labels);
    $escapedLabels = str_replace('"', '\\"', $labelsJson);
    
    $query = "mutation {
        change_column_metadata (
            board_id: $boardId, 
            column_id: \"$columnId\", 
            column_property: \"labels\", 
            value: \"$escapedLabels\"
        ) { id }
    }";
    
    $result = $monday->rawQuery($query);
    if (isset($result['data']['change_column_metadata'])) {
        echo "✅ OK\n";
    } else {
        echo "❌ ERROR: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
    }
}

// 1. Clasificación (HOT/WARM/COLD)
updateMetadata($monday, $boardId, NewColumnIds::CLASSIFICATION, [
    '0' => 'HOT', '1' => 'WARM', '2' => 'COLD'
]);

// 2. Rol Detectado
updateMetadata($monday, $boardId, NewColumnIds::ROLE_DETECTED, [
    '0' => 'Mission Partner',
    '1' => 'Rector/Director',
    '2' => 'Alcalde/Gobierno',
    '3' => 'Corporate',
    '4' => 'Maestro/Mentor',
    '5' => 'Interesado País',
    '6' => 'Joven'
]);

// 3. Tipo de Institución
updateMetadata($monday, $boardId, NewColumnIds::INST_TYPE, [
    '0' => 'Universidad', '1' => 'Colegio', '2' => 'Corporativo', '3' => 'Gobierno'
]);

echo "Configuración de etiquetas completada.\n";
?>
