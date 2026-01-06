<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';

$logFile = __DIR__ . '/final_debug.log';
file_put_contents($logFile, "Starting Final Debug Test\n", FILE_APPEND);

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;

    $columnValues = [
        'lead_email' => ['email' => 'test@robot.com', 'text' => 'test@robot.com'],
        'numeric_mkyn2py0' => 10
    ];

    file_put_contents($logFile, "Attempting createItem...\n", FILE_APPEND);
    $response = $monday->createItem($boardId, "DEBUG ROBOT", $columnValues);
    file_put_contents($logFile, "Response: " . json_encode($response) . "\n", FILE_APPEND);
    echo "SUCCESS: Check final_debug.log\n";

} catch (Exception $e) {
    file_put_contents($logFile, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "EXCEPTION: Check final_debug.log\n";
}
?>
