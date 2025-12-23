<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$query = 'query { boards (ids: ' . $boardId . ') { items_page (limit: 5) { items { id name created_at } } } }';
try {
    $result = $monday->query($query);
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
