<?php
// backup-monday-board.php
// Script to back up all items from a Monday.com board to a JSON file.

require_once '../config.php';
require_once 'MondayAPI.php';

// Ensure the constants are defined (they should be in config.php)
if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php. Please ensure config.php exists and contains these definitions.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;
$backupDir = __DIR__ . '/backups';

echo "Iniciando backup del tablero Monday.com (ID: {$boardId})...";

try {
    // Create backup directory if it doesn't exist
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0777, true)) {
            throw new Exception("No se pudo crear el directorio de backups: {$backupDir}");
        }
        echo "Directorio de backups creado: {$backupDir}";
    }

    $monday = new MondayAPI($apiToken);

    // Query to fetch all items from the board
    // Fetch relevant fields for each item
    $query = 'query ($boardId: ID!) {
        boards(ids: [$boardId]) {
            items_page {
                cursor
                items {
                    id
                    name
                    group {
                        id
                        title
                    }
                    column_values {
                        id
                        type
                        text
                        value
                    }
                }
            }
        }
    }';

    $allBoardItems = [];
    $hasMorePages = true;
    $cursor = null;

    // Paginate through items
    while ($hasMorePages) {
        $variables = ['boardId' => (int)$boardId];
        if ($cursor) {
            $queryWithCursor = 'query ($boardId: ID!, $cursor: String) {
                boards(ids: [$boardId]) {
                    items_page(limit: 500, cursor: $cursor) {
                        cursor
                        items {
                            id
                            name
                            group {
                                id
                                title
                            }
                            column_values {
                                id
                                type
                                text
                                value
                            }
                        }
                    }
                }
            }';
            $data = $monday->query($queryWithCursor, array_merge($variables, ['cursor' => $cursor]));
        } else {
            // First page, no cursor needed
            $queryFirstPage = 'query ($boardId: ID!) {
                boards(ids: [$boardId]) {
                    items_page(limit: 500) {
                        cursor
                        items {
                            id
                            name
                            group {
                                id
                                title
                            }
                            column_values {
                                id
                                type
                                text
                                value
                            }
                        }
                    }
                }
            }';
            $data = $monday->query($queryFirstPage, $variables);
        }

        $itemsPage = $data['boards'][0]['items_page'];
        $allBoardItems = array_merge($allBoardItems, $itemsPage['items']);
        $cursor = $itemsPage['cursor'];
        $hasMorePages = !empty($cursor);
    }
    
    echo "Total de items recuperados: " . count($allBoardItems) . "";

    // Prepare filename
    $timestamp = date('Ymd_His');
    $filename = "leads_backup_{$timestamp}.json";
    $filePath = "{$backupDir}/{$filename}";

    // Save data to JSON file
    if (file_put_contents($filePath, json_encode($allBoardItems, JSON_PRETTY_PRINT))) {
        echo "Backup completado exitosamente: {$filePath}";
    } else {
        throw new Exception("Error al guardar el archivo de backup: {$filePath}");
    }

} catch (Exception $e) {
    echo "Error durante el backup: " . $e->getMessage() . "";
}


