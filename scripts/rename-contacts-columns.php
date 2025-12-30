<?php
// rename-contacts-columns.php
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/config/config.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/MondayAPI.php';
require_once 'c:/Users/jesus/AI-Vault/projects/monday-automation/src/wordpress/NewColumnIds.php';

$boardId = '18392144862';
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "Limpiando nombres de columnas en el tablero de Contactos...\n";

$renasti = [
    NewColumnIds::ROLE_DETECTED => "Perfil Detectado",
    NewColumnIds::LEAD_SCORE => "Puntaje (Score)",
    NewColumnIds::CLASSIFICATION => "Clasificación",
    NewColumnIds::COUNTRY => "País",
    NewColumnIds::MISSION_PARTNER => "Partner Ref (Borrar si no se usa)",
    NewColumnIds::INST_TYPE => "Entidad",
    NewColumnIds::INTERNAL_NOTES => "Análisis IA",
    NewColumnIds::COMMENTS => "Resumen del Formulario",
    NewColumnIds::SOURCE_CHANNEL => "Origen",
    NewColumnIds::TYPE_OF_LEAD => "Categoría",
    NewColumnIds::NEXT_ACTION => "Seguimiento"
];

foreach ($renasti as $id => $newName) {
    echo "Renombrando $id a '$newName'... ";
    try {
        $monday->changeColumnTitle($boardId, $id, $newName);
        echo "✅\n";
    } catch (Exception $e) {
        echo "❌ (Error: " . $e->getMessage() . ")\n";
    }
}

echo "Proceso finalizado.\n";
?>
