<?php
// rename-board-leads.php
// Script para renombrar el tablero Leads a MC – Lead Master Intake

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "Renombrando tablero Leads (18392144864)...\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);

    // Mutación correcta para renombrar el tablero según la API de Monday
    $query = '
    mutation {
        update_board(board_id: 18392144864, board_attribute: name, new_value: "MC – Lead Master Intake")
    }';

    $result = $monday->query($query);

    if (isset($result['update_board']['board'])) {
        $board = $result['update_board']['board'];
        echo "✅ Tablero renombrado exitosamente:\n";
        echo "   ID: {$board['id']}\n";
        echo "   Nombre nuevo: {$board['name']}\n";
    } else {
        echo "❌ Error en la respuesta de la API: " . json_encode($result) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>