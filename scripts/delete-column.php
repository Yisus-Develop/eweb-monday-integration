<?php
// delete-column.php
// Deletes a specific column from the Monday.com board.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;
$columnIdToDelete = 'color_mkyn199t'; // The old, incorrect 'Clasificación' column

echo "Intentando eliminar la columna (ID: {$columnIdToDelete}) del tablero (ID: {$boardId})...\n";

try {
    $monday = new MondayAPI($apiToken);
    $deletedColumnId = $monday->deleteColumn($boardId, $columnIdToDelete);

    echo "Columna eliminada exitosamente.\n";
    echo "ID de la columna eliminada: {$deletedColumnId}\n";

} catch (Exception $e) {
    echo "Error al eliminar la columna: " . $e->getMessage() . "\n";
}

