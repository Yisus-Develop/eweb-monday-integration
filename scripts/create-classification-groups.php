<?php
// create-classification-groups.php
// Creates lead classification groups on the Monday.com board.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

$groupsToCreate = [
    '🔥 HOT Leads (Score > 20)',
    '🟡 WARM Leads (Score 10-20)',
    '🔵 COLD Leads (Score < 10)',
    '⚠️ Spam - Revisar'
];

echo "Iniciando la creación de grupos de clasificación en el tablero (ID: {$boardId})...";

try {
    $monday = new MondayAPI($apiToken);

    foreach ($groupsToCreate as $groupName) {
        echo "Creando grupo: '{$groupName}'...";
        $newGroupId = $monday->createGroup($boardId, $groupName);
        echo " -> Hecho (ID: {$newGroupId}).\n";
    }

    echo "\n¡Todos los grupos de clasificación han sido creados exitosamente!\n";

} catch (Exception $e) {
    echo "\nError durante la creación de grupos: " . $e->getMessage() . "\n";
}

