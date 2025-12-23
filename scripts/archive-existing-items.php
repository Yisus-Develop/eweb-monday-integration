<?php
// archive-existing-items.php
// Moves all existing items on the board to the specified archive group.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;
$archiveGroupId = 'group_mkyp7qng'; // Hardcoded from create-archive-group.php output

echo "Iniciando archivado de items en el tablero (ID: {$boardId}) al grupo (ID: {$archiveGroupId})...\n";

try {
    $monday = new MondayAPI($apiToken);

    // 1. Get all items from the board
    $allItems = [];
    $cursor = null;
    do {
        $query = 'query ($boardId: ID!, $cursor: String) {
            boards (ids: [$boardId]) {
                items_page (limit: 100, cursor: $cursor) {
                    cursor
                    items {
                        id
                        group { id }
                    }
                }
            }
        }';
        $variables = ['boardId' => (int)$boardId, 'cursor' => $cursor];
        $data = $monday->query($query, $variables);

        $itemsPage = $data['boards'][0]['items_page'];
        $allItems = array_merge($allItems, $itemsPage['items']);
        $cursor = $itemsPage['cursor'];

    } while (!empty($cursor));

    echo "Total de items encontrados: " . count($allItems) . "\n";

    // 2. Move each item to the archive group if not already there
    $movedCount = 0;
    $skippedCount = 0;
    foreach ($allItems as $item) {
        if ($item['group']['id'] !== $archiveGroupId) {
            echo "Moviendo item ID: {$item['id']}...";
            $monday->moveItemToGroup($item['id'], $archiveGroupId);
            echo " -> Hecho.\n";
            $movedCount++;
        } else {
            $skippedCount++;
        }
    }

    echo "\nArchivado completado.\n";
    echo "Items movidos: {$movedCount}\n";
    echo "Items omitidos (ya en el grupo de archivo): {$skippedCount}\n";

} catch (Exception $e) {
    echo "\nError durante el archivado: " . $e->getMessage() . "\n";
}

