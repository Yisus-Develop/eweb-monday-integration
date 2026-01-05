<?php
/**
 * recovery-tool.php
 * 
 * Herramienta para procesar leads que fallaron anteriormente.
 * Puedes proporcionar un array de leads y este script los enviará a Monday
 * usando la estructura corregida.
 */

require_once 'NewColumnIds.php';
require_once 'StatusConstants.php';
require_once 'LeadScoring.php';
require_once 'MondayAPI.php';
require_once '../../config/config.php';

// Configuración
$dryRun = false; // Cambiar a false para enviar de verdad a Monday

// Lista de leads a recuperar (Ejemplo de estructura)
// Puedes llenar este array con los datos del log o DB
$leadsToRecover = [
    /*
    [
        'nombre' => 'Ejemplo Recuperado',
        'email' => 'recuperado@mail.com',
        'pais_cf7' => 'México',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad'
    ],
    */
];

if (empty($leadsToRecover)) {
    die("⚠️ No hay leads en la lista para recuperar. Edita este archivo y añade los leads en el array \$leadsToRecover.\n");
}

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "🚀 Iniciando recuperación de " . count($leadsToRecover) . " leads...\n\n";

foreach ($leadsToRecover as $index => $data) {
    echo "[" . ($index + 1) . "] Procesando: " . ($data['nombre'] ?? $data['your-name'] ?? 'Sin Nombre') . " (" . $data['email'] . ")\n";
    
    try {
        // Mapeo similar al webhook-handler
        $scoringData = [
            'name' => $data['nombre'] ?? $data['your-name'] ?? 'Sin Nombre',
            'email' => $data['email'] ?? $data['your-email'] ?? '',
            'phone' => $data['telefono'] ?? $data['your-phone'] ?? '',
            'country' => $data['pais_cf7'] ?? $data['pais_otro'] ?? '',
            'perfil' => $data['perfil'] ?? 'general',
            'tipo_institucion' => $data['tipo_institucion'] ?? '',
            // ... añadir más campos si es necesario
        ];

        // Calcular Score
        $scoreResult = LeadScoring::calculate($scoringData);

        if ($dryRun) {
            echo "  🔍 [DRY RUN] Score: " . $scoreResult['total'] . " | Clase: " . $scoreResult['priority_label'] . "\n";
            continue;
        }

        // Preparar Columnas
        $columnValues = [
            NewColumnIds::EMAIL => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
            NewColumnIds::LEAD_SCORE => (int)$scoreResult['total'],
            NewColumnIds::CLASSIFICATION => ['label' => $scoreResult['priority_label']],
            NewColumnIds::ROLE_DETECTED => ['label' => StatusConstants::getRoleLabel($scoreResult['detected_role'])],
            NewColumnIds::COUNTRY => $scoringData['country'],
            NewColumnIds::ENTRY_DATE => ['date' => date('Y-m-d')],
            // ... otras columnas
        ];

        // Enviar a Monday
        $resp = $monday->createItem($boardId, $scoringData['name'], $columnValues);
        $itemId = $resp['create_item']['id'] ?? null;

        if ($itemId) {
            echo "  ✅ Éxito! ID en Monday: $itemId\n";
            
            // Actualizar dropdowns
            try {
                $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['label' => $scoreResult['tipo_lead']]);
                $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['label' => $scoreResult['canal_origen']]);
                $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['label' => $scoreResult['idioma']]);
            } catch (Exception $e) {
                echo "  ⚠️ Error parcial en dropdowns: " . $e->getMessage() . "\n";
            }
        }

    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
    }
    echo "------------------------------------------\n";
}

echo "\n🏁 Proceso de recuperación finalizado.\n";
