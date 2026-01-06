<?php
require_once 'MondayAPI.php';
require_once '../../config/config.php'; // Correct path to config

$token = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

$monday = new MondayAPI($token);

try {
    echo "Auditing Board: $boardId\n";
    $query = '{
        boards (ids: [' . $boardId . ']) {
            name
            columns {
                id
                title
                type
                settings_str
            }
        }
    }';
    $data = $monday->query($query);
    
    if (empty($data['boards'])) {
        die("Board not found.\n");
    }
    
    $board = $data['boards'][0];
    echo "Board Name: " . $board['name'] . "\n\n";
    echo str_pad("ID", 25) . " | " . str_pad("Title", 30) . " | " . "Type\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($board['columns'] as $col) {
        echo str_pad($col['id'], 25) . " | " . str_pad($col['title'], 30) . " | " . $col['type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
