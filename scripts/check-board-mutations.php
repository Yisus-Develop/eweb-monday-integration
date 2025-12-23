<?php
// check-board-mutations.php
// Script para verificar qué mutaciones están disponibles para tableros

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "Verificando mutaciones disponibles para tableros...\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Intentamos usar la mutación update_board_name si existe
    $query = '
    mutation {
        update_board_name(board_id: 18392144864, name: "MC – Lead Master Intake Test") {
            board {
                id
                name
            }
        }
    }';
    
    $result = $monday->rawQuery($query);
    echo "Resultado de update_board_name: " . json_encode($result) . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>