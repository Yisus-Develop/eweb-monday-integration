<?php
require_once 'MondayAPI.php';
require_once '../../config/config.php';

$token = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;
$itemId = '7143431669'; // The ID from my test

$monday = new MondayAPI($token);

try {
    echo "Checking Item: $itemId\n";
    $query = '{
        items (ids: [' . $itemId . ']) {
            name
            column_values {
                id
                text
                value
            }
        }
    }';
    $data = $monday->query($query);
    
    if (empty($data['items'])) {
        die("Item not found.\n");
    }
    
    $item = $data['items'][0];
    echo "Item Name: " . $item['name'] . "\n\n";
    
    foreach ($item['column_values'] as $val) {
        echo "Column [{$val['id']}]: {$val['text']} (Value: {$val['value']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
