<?php
// update-labels.php
// Sincroniza las etiquetas de las columnas Status con lo que espera el sistema

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

echo "=== Sincronizando Etiquetas de Monday.com ===\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    // 1. Actualizar Clasificación
    echo "Actualizando etiquetas de 'Clasificación'...\n";
    $query = 'mutation {
        change_column_metadata (
            board_id: '.$boardId.', 
            column_id: "classification_status", 
            column_property: "labels", 
            value: "{\"0\":\"HOT\", \"1\":\"WARM\", \"2\":\"COLD\"}"
        ) { id }
    }';
    
    $result = $monday->rawQuery($query);
    if (isset($result['data']['change_column_metadata'])) {
        echo "✅ Etiquetas de Clasificación actualizadas.\n";
    } else {
        echo "❌ Error actualizando Clasificación: " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
    }

    // 2. Actualizar Rol Detectado
    echo "Actualizando etiquetas de 'Rol Detectado'...\n";
    $rolesMap = [
        "0" => "Mission Partner",
        "1" => "Rector/Director",
        "2" => "Alcalde/Gobierno",
        "3" => "Corporate",
        "4" => "Maestro/Mentor",
        "5" => "Joven"
    ];
    $rolesValue = json_encode($rolesMap);
    $rolesValue = str_replace('"', '\"', $rolesValue);
    
    $queryRoles = 'mutation {
        change_column_metadata (
            board_id: '.$boardId.', 
            column_id: "role_detected", 
            column_property: "labels", 
            value: "'.$rolesValue.'"
        ) { id }
    }';
    
    $resultRoles = $monday->rawQuery($queryRoles);
    if (isset($resultRoles['data']['change_column_metadata'])) {
        echo "✅ Etiquetas de Rol Detectado actualizadas.\n";
    } else {
        echo "❌ Error actualizando Rol Detectado: " . json_encode($resultRoles['errors'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
