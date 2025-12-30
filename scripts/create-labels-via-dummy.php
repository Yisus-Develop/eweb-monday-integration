<?php
// create-labels-via-dummy.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Creando etiquetas mediante item de prueba...\n";

try {
    // 1. Crear item dummy
    $itemResult = $monday->createItem($boardId, "DUMMY TEST LABELS (DELETE ME)");
    $itemId = $itemResult['create_item']['id'];
    echo "Item creado: $itemId\n";

    // 2. Definir valores que queremos que existan como etiquetas
    $labelsToCreate = [
        NewColumnIds::CLASSIFICATION => ['HOT', 'WARM', 'COLD'],
        NewColumnIds::ROLE_DETECTED => ['Mission Partner', 'Rector/Director', 'Alcalde/Gobierno', 'Corporate', 'Maestro/Mentor', 'Joven', 'Interesado País'],
        NewColumnIds::INST_TYPE => ['Universidad', 'Colegio', 'Corporativo', 'Gobierno'],
        NewColumnIds::TYPE_OF_LEAD => ['Universidad', 'Escuela', 'Empresa', 'Ciudad', 'Otro'],
        NewColumnIds::SOURCE_CHANNEL => ['Website', 'WhatsApp', 'Email', 'Mission Partner', 'Otro'],
        NewColumnIds::LANGUAGE => ['Español', 'Inglés', 'Portugués']
    ];

    foreach ($labelsToCreate as $colId => $labels) {
        foreach ($labels as $label) {
            echo "Creando etiqueta '$label' en columna $colId... ";
            try {
                $monday->changeSimpleColumnValue($boardId, $itemId, $colId, $label, true);
                echo "✅\n";
            } catch (Exception $e) {
                echo "❌ (Ya existe o error: " . $e->getMessage() . ")\n";
            }
        }
    }

    // 3. Borrar item dummy
    echo "Borrando item de prueba...\n";
    $monday->query("mutation { delete_item (item_id: $itemId) { id } }");
    echo "Hecho.\n";

} catch (Exception $e) {
    echo "❌ ERROR GLOBAL: " . $e->getMessage() . "\n";
}
?>
