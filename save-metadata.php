<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$query = 'query { boards (ids: ' . $boardId . ') { columns { id title settings_str } } }';
try {
    $result = $monday->query($query);
    file_put_contents('column-metadata.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "Metadata saved to column-metadata.json\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
