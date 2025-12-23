<?php
// add-secondary-columns.php
// Adds secondary columns to the Monday.com board as per the implementation roadmap.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

echo "Iniciando la creación de columnas secundarias en el tablero (ID: {$boardId})...\n";

$secondaryColumns = [
    ['title' => 'Fecha de Entrada', 'type' => 'date'],
    ['title' => 'Próxima Acción', 'type' => 'date'],
    ['title' => 'Notas Internas', 'type' => 'long_text']
];

try {
    $monday = new MondayAPI($apiToken);

    foreach ($secondaryColumns as $column) {
        echo "Creando columna: '{$column['title']}' (Tipo: {$column['type']})...";
        $columnId = $monday->createColumn($boardId, $column['title'], $column['type']);
        echo " -> Columna '{$column['title']}' creada con ID: {$columnId}\n";
    }

    echo "\n¡Todas las columnas secundarias han sido creadas exitosamente!\n";

} catch (Exception $e) {
    echo "\nError durante la creación de columnas: " . $e->getMessage() . "\n";
}

