<?php
// rename-board-activities.php
// Script para renombrar el tablero Actividades a MC – Actividades

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "Renombrando tablero Actividades (18392144861)...\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Mutación para renombrar el tablero
    $query = '
    mutation {
        update_board(board_id: 18392144861, board_attribute: name, new_value: "MC – Actividades")
    }';
    
    $result = $monday->query($query);
    
    if (isset($result['update_board'])) {
        echo "✅ Tablero renombrado exitosamente:\n";
        $responseData = json_decode($result['update_board'], true);
        if ($responseData && isset($responseData['success']) && $responseData['success']) {
            echo "   Nombre nuevo: MC – Actividades\n";
        } else {
            echo "   Datos de respuesta: " . $result['update_board'] . "\n";
        }
    } else {
        echo "❌ Error en la respuesta de la API: " . json_encode($result) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>