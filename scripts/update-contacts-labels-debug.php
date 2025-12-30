<?php
// update-contacts-labels-debug.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

function debugMutation($monday, $label, $query) {
    echo "Intentando: $label...\n";
    $result = $monday->rawQuery($query);
    if (isset($result['errors'])) {
        echo "❌ ERROR en $label: " . json_encode($result['errors'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "✅ ÉXITO en $label\n";
    }
}

// 1. Clasificación (HOT/WARM/COLD)
$q1 = "mutation { update_column (board_id: $boardId, column_id: \"" . NewColumnIds::CLASSIFICATION . "\", board_attribute: settings, new_value: \"{\\\"labels\\\":{\\\"0\\\":\\\"HOT\\\",\\\"1\\\":\\\"WARM\\\",\\\"2\\\":\\\"COLD\\\"}}\") { id } }";
debugMutation($monday, "Clasificación", $q1);

// 2. Rol Detectado
$q2 = "mutation { update_column (board_id: $boardId, column_id: \"" . NewColumnIds::ROLE_DETECTED . "\", board_attribute: settings, new_value: \"{\\\"labels\\\":{\\\"0\\\":\\\"Mission Partner\\\",\\\"1\\\":\\\"Rector/Director\\\",\\\"2\\\":\\\"Alcalde/Gobierno\\\",\\\"3\\\":\\\"Corporate\\\",\\\"4\\\":\\\"Maestro/Mentor\\\",\\\"5\\\":\\\"Interesado País\\\",\\\"6\\\":\\\"Joven\\\"}}\") { id } }";
debugMutation($monday, "Rol Detectado", $q2);
?>
