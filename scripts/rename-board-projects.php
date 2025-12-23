<?php
// rename-board-projects.php
// Script para renombrar el tablero Proyectos de clientes a MC – Proyectos de Clientes

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "Renombrando tablero Proyectos de clientes (18392144859)...\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Mutación para renombrar el tablero
    $query = '
    mutation {
        update_board(board_id: 18392144859, board_attribute: name, new_value: "MC – Proyectos de Clientes")
    }';
    
    $result = $monday->query($query);
    
    if (isset($result['update_board'])) {
        echo "✅ Tablero renombrado exitosamente:\n";
        $responseData = json_decode($result['update_board'], true);
        if ($responseData && isset($responseData['success']) && $responseData['success']) {
            echo "   Nombre nuevo: MC – Proyectos de Clientes\n";
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