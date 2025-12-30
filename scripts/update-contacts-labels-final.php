<?php
// update-contacts-labels-final.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Configurando etiquetas para el tablero de Contactos...\n";

function updateColumnSettings($monday, $boardId, $columnId, $settings) {
    echo "Actualizando columna: $columnId... ";
    
    // Usamos variables para evitar problemas de escape en el JSON
    $query = 'mutation ($boardId: ID!, $columnId: String!, $settings: JSON!) {
        update_column (
            board_id: $boardId, 
            id: $columnId, 
            settings: $settings
        ) { id }
    }';
    
    $variables = [
        'boardId' => $boardId,
        'columnId' => $columnId,
        'settings' => json_encode($settings)
    ];
    
    try {
        $result = $monday->query($query, $variables);
        echo "✅ OK\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
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
