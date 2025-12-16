<?php
// inspect-monday-board.php
// Script de Auditoría Profunda del Tablero Monday

require_once 'config.php';
require_once 'MondayAPI.php';

echo "--- Auditoría de Tablero Monday (ID: " . MONDAY_BOARD_ID . ") ---\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    // ID del tablero 'Leads' encontrado en el escaneo anterior
    $boardId = 18392144864; 

    // Consultar BÁSICOS del tablero para evitar errores de schema
    $query = 'query ($boardId: ID!) {
        boards (ids: [$boardId]) {
            name
            state
            groups {
                id
                title
            }
            columns {
                id
                title
                type
            }
        }
    }';

    $result = $monday->query($query, ['boardId' => $boardId]);

    if (empty($result['boards'])) {
        die("❌ Tablero no encontrado.\n");
    }

    $board = $result['boards'][0];

    // Generar Reporte JSON para análisis
    $report = [
        'info' => [
            'name' => $board['name'],
            'state' => $board['state'],
            'description' => $board['description']
        ],
        'groups' => $board['groups'],
        'columns' => []
    ];

    foreach ($board['columns'] as $col) {
        $report['columns'][] = [
            'title' => $col['title'],
            'id' => $col['id'],
            'type' => $col['type']
        ];
    }

    // Guardar reporte crudo
    file_put_contents('monday_board_dump.json', json_encode($report, JSON_PRETTY_PRINT));
    
    echo "✅ Datos extraídos correctamente.\n";
    echo "Nombre: {$board['name']}\n";
    echo "Grupos: " . count($board['groups']) . "\n";
    echo "Columnas: " . count($board['columns']) . "\n";
    echo "Reporte guardado en 'monday_board_dump.json' para análisis.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
