<?php
// update-contacts-labels.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Configurando etiquetas para el tablero de Contactos (Usando id y settings)...\n";

function updateColumnSettings($monday, $boardId, $columnId, $settings) {
    echo "Actualizando columna: $columnId...\n";
    $settingsJson = json_encode($settings);
    // Escapar comillas para la mutación inline
    $escapedSettings = str_replace('"', '\\"', $settingsJson);
    
    // Basado en introspección, id es el nombre del argumento
    $query = "mutation { 
        update_column (
            board_id: $boardId, 
            id: \"$columnId\", 
            settings: \"$escapedSettings\"
        ) { id } 
    }";
    
    try {
        $result = $monday->rawQuery($query);
        if (isset($result['errors'])) {
            echo "❌ ERROR: " . json_encode($result['errors']) . "\n";
        } else {
            echo "✅ ÉXITO\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
}

// 1. Clasificación (HOT/WARM/COLD)
updateColumnSettings($monday, $boardId, NewColumnIds::CLASSIFICATION, [
    'labels' => ['0' => 'HOT', '1' => 'WARM', '2' => 'COLD']
]);

// 2. Rol Detectado
updateColumnSettings($monday, $boardId, NewColumnIds::ROLE_DETECTED, [
    'labels' => [
        '0' => 'Mission Partner',
        '1' => 'Rector/Director',
        '2' => 'Alcalde/Gobierno',
        '3' => 'Corporate',
        '4' => 'Maestro/Mentor',
        '5' => 'Interesado País',
        '6' => 'Joven'
    ]
]);

// 3. Tipo de Institución
updateColumnSettings($monday, $boardId, NewColumnIds::INST_TYPE, [
    'labels' => ['0' => 'Universidad', '1' => 'Colegio', '2' => 'Corporativo', '3' => 'Gobierno']
]);

// Para dropdowns se usa create_labels_if_missing suele ser más fácil pero vamos a probar con settings
// 4. Canal de Origen
updateColumnSettings($monday, $boardId, NewColumnIds::SOURCE_CHANNEL, [
    'labels' => [
        ['name' => 'Website'],
        ['name' => 'WhatsApp'],
        ['name' => 'Email'],
        ['name' => 'Mission Partner'],
        ['name' => 'Otro']
    ]
]);

// 5. Tipo de Lead
updateColumnSettings($monday, $boardId, NewColumnIds::TYPE_OF_LEAD, [
    'labels' => [
        ['name' => 'Universidad'],
        ['name' => 'Escuela'],
        ['name' => 'Empresa'],
        ['name' => 'Ciudad'],
        ['name' => 'Otro']
    ]
]);

// 6. Idioma
updateColumnSettings($monday, $boardId, NewColumnIds::LANGUAGE, [
    'labels' => [
        ['name' => 'Español'],
        ['name' => 'Inglés'],
        ['name' => 'Portugués']
    ]
]);

echo "Configuración de etiquetas completada.\n";
?>
