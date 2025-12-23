<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "--- AUDITORÍA DE TABLERO MONDAY ---\n\n";

$query = 'query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        columns {
            id
            title
            type
        }
        items_page (limit: 5) {
            items {
                id
                name
                column_values {
                    id
                    text
                    value
                }
            }
        }
    }
}';

try {
    $data = $monday->query($query, ['boardId' => (int)$boardId]);
    $board = $data['boards'][0] ?? null;

    if (!$board) {
        die("No se encontró el tablero.");
    }

    echo "COLUMNAS ACTUALES EN EL TABLERO:\n";
    foreach ($board['columns'] as $col) {
        echo "ID: {$col['id']} | Título: {$col['title']} | Tipo: {$col['type']}\n";
    }

    echo "\nÚLTIMOS ELEMENTOS Y SUS VALORES:\n";
    foreach ($board['items_page']['items'] as $item) {
        echo "\nID: {$item['id']} | Nombre: {$item['name']}\n";
        foreach ($item['column_values'] as $val) {
            // Filtrar solo las que tienen datos o son importantes
            if (!empty($val['text'])) {
                echo "   -> {$val['id']}: {$val['text']}\n";
            } else {
                 echo "   -> {$val['id']}: [VACÍO]\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
