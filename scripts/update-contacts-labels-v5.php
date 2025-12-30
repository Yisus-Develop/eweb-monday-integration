<?php
// update-contacts-labels-v5.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Configurando etiquetas para el tablero de Contactos (v5 - Modern API)...\n";

function updateStatusLabels($monday, $boardId, $columnId, $labels) {
    echo "Actualizando columna Status: $columnId... ";
    
    // Convertir labels a formato de objeto GraphQL
    $labelsObj = "";
    foreach ($labels as $key => $val) {
        $labelsObj .= "\\\"$key\\\": \\\"$val\\\", ";
    }
    $labelsObj = rtrim($labelsObj, ", ");
    
    // Usamos variables para mayor seguridad
    $query = 'mutation ($boardId: ID!, $columnId: String!, $labelsJson: JSON!) {
        update_column (
            board_id: $boardId, 
            id: $columnId, 
            settings: $labelsJson
        ) { id }
    }';
    
    // El argumento settings en update_column es un JSON! (String as JSON)
    $settings = json_encode(['labels' => $labels]);
    
    $variables = [
        'boardId' => $boardId,
        'columnId' => $columnId,
        'labelsJson' => $settings
    ];
    
    try {
        // Obligamos a usar una versión moderna
        $result = $monday->query($query, $variables);
        echo "✅ OK\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

// Actualizar MondayAPI para asegurar versión 2024-04
// (Ya lo hice en el archivo pero por si acaso me aseguro de que el token sea correcto)

updateStatusLabels($monday, $boardId, NewColumnIds::CLASSIFICATION, [
    "0" => "HOT", "1" => "WARM", "2" => "COLD"
]);

updateStatusLabels($monday, $boardId, NewColumnIds::ROLE_DETECTED, [
    "0" => "Mission Partner",
    "1" => "Rector/Director",
    "2" => "Alcalde/Gobierno",
    "3" => "Corporate",
    "4" => "Maestro/Mentor",
    "5" => "Interesado País",
    "6" => "Joven"
]);

updateStatusLabels($monday, $boardId, NewColumnIds::INST_TYPE, [
    "0" => "Universidad", "1" => "Colegio", "2" => "Corporativo", "3" => "Gobierno"
]);

echo "Configuración de etiquetas completada.\n";
?>
