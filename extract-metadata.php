<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$query = 'query { boards (ids: ' . $boardId . ') { columns { id title settings_str } } }';
try {
    $result = $monday->query($query);
    foreach ($result['boards'][0]['columns'] as $col) {
        if ($col['id'] === 'classification_status' || $col['id'] === 'role_detected_new' || $col['id'] === 'lead_status') {
            echo "ID: " . $col['id'] . " | Title: " . $col['title'] . "\n";
            echo "Settings: " . $col['settings_str'] . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
