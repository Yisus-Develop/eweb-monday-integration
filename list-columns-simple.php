<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$query = 'query { boards (ids: ' . $boardId . ') { columns { id title } } }';
try {
    $result = $monday->query($query);
    foreach ($result['boards'][0]['columns'] as $col) {
        echo "ID: " . $col['id'] . " | Title: " . $col['title'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
