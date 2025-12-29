<?php
// harmonize-contacts-columns.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';

$boardId = '18392144862'; // MC – Contactos
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Iniciando armonización de columnas para el tablero de Contactos ($boardId)...\n";

$columnsToCreate = [
    ['title' => 'Lead Score', 'type' => 'numbers', 'id' => 'lead_score_mc'],
    ['title' => 'Clasificación', 'type' => 'status', 'id' => 'classification_mc'],
    ['title' => 'Rol Detectado', 'type' => 'status', 'id' => 'role_mc'],
    ['title' => 'País', 'type' => 'text', 'id' => 'country_mc'],
    ['title' => 'Mission Partner', 'type' => 'text', 'id' => 'partner_mc'],
    ['title' => 'Fecha de Entrada', 'type' => 'date', 'id' => 'entry_date_mc'],
    ['title' => 'Próxima Acción', 'type' => 'date', 'id' => 'next_action_mc'],
    ['title' => 'Tipo de Lead', 'type' => 'dropdown', 'id' => 'lead_type_mc'],
    ['title' => 'Canal de Origen', 'type' => 'dropdown', 'id' => 'source_channel_mc'],
    ['title' => 'Idioma', 'type' => 'dropdown', 'id' => 'language_mc'],
    ['title' => 'Monto', 'type' => 'numbers', 'id' => 'amount_mc'],
    ['title' => 'Ciudad', 'type' => 'text', 'id' => 'city_mc'],
    ['title' => 'Tipo de Institución', 'type' => 'status', 'id' => 'inst_type_mc'],
    ['title' => 'Notas Internas', 'type' => 'long_text', 'id' => 'internal_notes_mc']
];

foreach ($columnsToCreate as $col) {
    echo "Creando columna: {$col['title']} ({$col['type']})...\n";
    try {
        $query = "mutation { create_column (board_id: $boardId, title: \"{$col['title']}\", column_type: {$col['type']}) { id } }";
        $result = $monday->query($query);
        if (isset($result['create_column'])) {
            echo "✅ Creada con ID: " . $result['create_column']['id'] . "\n";
        } else {
            echo "❌ Error: " . json_encode($result) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\nArmonización completada. Actualiza NewColumnIds.php con estos nuevos IDs.\n";
?>
