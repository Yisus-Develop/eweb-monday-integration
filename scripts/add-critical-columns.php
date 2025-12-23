<?php
// add-critical-columns.php
// Adds critical columns to the Monday.com board as per the implementation roadmap.

require_once '../config.php';
require_once 'MondayAPI.php';

if (!defined('MONDAY_API_TOKEN') || !defined('MONDAY_BOARD_ID')) {
    die("Error: MONDAY_API_TOKEN or MONDAY_BOARD_ID not defined in config.php.");
}

$apiToken = MONDAY_API_TOKEN;
$boardId = MONDAY_BOARD_ID;

echo "Iniciando la creación de columnas críticas en el tablero (ID: {$boardId})...\n";

$criticalColumns = [
    [
        'title' => 'Clasificación',
        'type' => 'status'
    ],
    [
        'title' => 'Tipo de Lead',
        'type' => 'dropdown',
        'settings' => [
            'labels' => [
                ['name' => 'Universidad'],
                ['name' => 'Escuela'],
                ['name' => 'Ciudad'],
                ['name' => 'Empresa'],
                ['name' => 'Mission Partner'],
                ['name' => 'Mentor'],
                ['name' => 'Joven'],
                ['name' => 'Otro']
            ]
        ]
    ],
    [
        'title' => 'Canal de Origen',
        'type' => 'dropdown',
        'settings' => [
            'labels' => [
                ['name' => 'Website'],
                ['name' => 'WhatsApp'],
                ['name' => 'Redes'],
                ['name' => 'Mission Partner'],
                ['name' => 'Evento'],
                ['name' => 'Email'],
                ['name' => 'Newsletter'],
                ['name' => 'Otro']
            ]
        ]
    ],
    [
        'title' => 'Mission Partner',
        'type' => 'text',
        'settings' => []
    ],
    [
        'title' => 'Idioma',
        'type' => 'dropdown',
        'settings' => [
            'labels' => [
                ['name' => 'Español'],
                ['name' => 'Inglés'],
                ['name' => 'Portugués']
            ]
        ]
    ]
];

try {
    $monday = new MondayAPI($apiToken);

    foreach ($criticalColumns as $column) {
        echo "Creando columna: '{$column['title']}' (Tipo: {$column['type']})...\n";
        $columnId = $monday->createColumnWithSettings($boardId, $column['title'], $column['type'], $column['settings']);
        echo " -> Columna '{$column['title']}' creada con ID: {$columnId}\n";
    }

    echo "\n¡Todas las columnas críticas han sido creadas exitosamente!\n";

} catch (Exception $e) {
    echo "\nError durante la creación de columnas: " . $e->getMessage() . "\n";
}
