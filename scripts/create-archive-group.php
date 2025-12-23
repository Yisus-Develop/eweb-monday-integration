<?php
// create-archive-group.php
// Creates a new group in the Monday.com board for archiving old items.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;
$groupName = "🗄️ Archive - Pre-Integration";

echo "Intentando crear el grupo '{$groupName}' en el tablero (ID: {$boardId})...";

try {
    $monday = new MondayAPI($apiToken);
    $newGroupId = $monday->createGroup($boardId, $groupName);

    echo "Grupo creado exitosamente.";
    echo "Nombre del Grupo: {$groupName}";
    echo "ID del Grupo: {$newGroupId}";

} catch (Exception $e) {
    echo "Error al crear el grupo: " . $e->getMessage() . "\n";
}

