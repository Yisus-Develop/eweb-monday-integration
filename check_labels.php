<?php
require_once 'MondayAPI.php';
require_once '../../config/config.php';

$token = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

$monday = new MondayAPI($token);

try {
    $query = '{
        boards (ids: [' . $boardId . ']) {
            columns {
                id
                title
                type
                settings_str
            }
        }
    }';
    $data = $monday->query($query);
    
    foreach ($data['boards'][0]['columns'] as $col) {
        if (in_array($col['type'], ['status', 'dropdown', 'color'])) {
            echo "Column [{$col['id']}] ({$col['title']}):\n";
            $settings = json_decode($col['settings_str'], true);
            if (isset($settings['labels'])) {
                print_r($settings['labels']);
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
