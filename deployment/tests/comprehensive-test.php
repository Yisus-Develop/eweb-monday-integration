<?php
// comprehensive-test.php - Prueba completa con todos los perfiles
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/NewColumnIds.php';
require_once __DIR__ . '/StatusConstants.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== PRUEBA COMPLETA: TODOS LOS PERFILES ===\n\n";

$scenarios = [
    ['nombre' => 'Ana Institución', 'email' => 'ana@universidad.mx', 'phone' => '+52 55 1111 1111', 'country' => 'México', 'perfil' => 'institucion'],
    ['nombre' => 'Carlos Ciudad', 'email' => 'carlos@gobierno.co', 'phone' => '300 2222 222', 'country' => 'Colombia', 'perfil' => 'ciudad'],
    ['nombre' => 'Diana Empresa', 'email' => 'diana@empresa.es', 'phone' => '+34 91 333 3333', 'country' => 'España', 'perfil' => 'empresa'],
    ['nombre' => 'Eduardo Pioneer', 'email' => 'eduardo@pioneer.org', 'phone' => '+1 555 4444', 'country' => 'Estados Unidos', 'perfil' => 'pioneer'],
    ['nombre' => 'Fernanda Mentor', 'email' => 'fernanda@escuela.ar', 'phone' => '+54 11 5555 5555', 'country' => 'Argentina', 'perfil' => 'mentor'],
    ['nombre' => 'Gabriel País', 'email' => 'gabriel@ministerio.cl', 'phone' => '+56 2 6666 6666', 'country' => 'Chile', 'perfil' => 'pais'],
    ['nombre' => 'Helena Zer', 'email' => 'helena@joven.pe', 'phone' => '+51 1 7777 7777', 'country' => 'Perú', 'perfil' => 'zer'],
    ['nombre' => 'Ignacio General', 'email' => 'ignacio@general.uy', 'phone' => '+598 2 8888 8888', 'country' => 'Uruguay', 'perfil' => 'general']
];

foreach ($scenarios as $data) {
    echo "[{$data['nombre']}]\n";
    
    $scoreResult = LeadScoring::calculate($data);
    $label = StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = StatusConstants::getGroupById($label);
    
    echo "   Perfil: {$scoreResult['tipo_lead']}\n";
    
    $columnValues = [
        NewColumnIds::LEAD_EMAIL => ['email' => $data['email'], 'text' => $data['email']],
        NewColumnIds::NUMERIC_SCORE => (string)$scoreResult['total'],
        NewColumnIds::CLASSIFICATION_STATUS => ['label' => $label],
        NewColumnIds::COUNTRY_TEXT => $data['country'],
        NewColumnIds::DATE_CREATED => ['date' => date('Y-m-d')],
        NewColumnIds::RAW_DATA_JSON => json_encode($data, JSON_UNESCAPED_UNICODE)
    ];

    try {
        $itemResponse = $monday->createItem($boardId, $data['nombre'], $columnValues, $groupId);
        $itemId = $itemResponse['create_item']['id'] ?? null;

        if ($itemId) {
            echo "   ✅ Item #$itemId creado.\n";
            
            // Teléfono
            if (!empty($scoreResult['clean_phone'])) {
                $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LEAD_PHONE, $scoreResult['clean_phone']);
            }

            // Tipo de Lead - IMPORTANTE: create_labels_if_missing = true
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, $scoreResult['tipo_lead'], true);
            
            // Otros dropdowns
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, 'Website', true);
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, $scoreResult['idioma'], true);
            
            echo "   ✅ Completado.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== PRUEBA COMPLETADA ===\n";
?>
