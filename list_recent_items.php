<?php
require_once 'MondayAPI.php';
require_once '../../config/config.php';

$token = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

$monday = new MondayAPI($token);

try {
    echo "Checking Board: $boardId\n";
    $query = '{
        boards (ids: [' . $boardId . ']) {
            items_page (limit: 5) {
                items {
                    id
                    name
                    created_at
                }
            }
        }
    }';
    $data = $monday->query($query);
    
    if (empty($data['boards'])) {
        die("Board not found.\n");
    }
    
    $items = $data['boards'][0]['items_page']['items'];
    echo "Recent Items:\n";
    foreach ($items as $item) {
        echo "[{$item['id']}] {$item['name']} ({$item['created_at']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
