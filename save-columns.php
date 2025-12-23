<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$query = 'query { boards (ids: ' . $boardId . ') { columns { id title type } } }';
try {
    $result = $monday->query($query);
    file_put_contents('board-columns.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "Columns saved to board-columns.json\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
