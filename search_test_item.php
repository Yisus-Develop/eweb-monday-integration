<?php
require_once 'MondayAPI.php';
require_once '../../config/config.php';

$token = MONDAY_API_TOKEN;
$monday = new MondayAPI($token);

try {
    echo "Searching for 'Test User Antigravity'...\n";
    $query = '{
        items_by_column_values (board_id: 18392144862, column_id: "name", column_value: "Test User Antigravity") {
            id
            name
            board {
                id
                name
            }
            column_values {
                id
                text
            }
        }
    }';
    $data = $monday->query($query);
    
    if (empty($data['items_by_column_values'])) {
        echo "Not found on board 18392144862. Searching across all boards (slow)...\n";
        // Actually items_by_column_values requires board_id.
        // We can just list all boards and check if needed, but let's try searching items directly if available.
        // Or search items by name globally? Monday API v2 doesn't have a global search easily without search_items_by_name (legacy).
    } else {
        foreach ($data['items_by_column_values'] as $item) {
            echo "Found Item: [{$item['id']}] {$item['name']} on Board [{$item['board']['id']}]\n";
            foreach ($item['column_values'] as $val) {
                echo "- {$val['id']}: {$val['text']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
