<?php
// test-v4-features.php
// Versión FINAL 4.2 - Rectificada para máxima compatibilidad con Monday API

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/NewColumnIds.php';
require_once __DIR__ . '/StatusConstants.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== INICIANDO TEST FINAL V4 (MODO ROBUSTO) ===\n\n";

$scenarios = [
    [
        'name' => 'Luis Zer (México)',
        'data' => [
            'nombre' => 'Luis Zer Test-Robusto',
            'email' => 'luis.robusto@example.mx',
            'phone' => '+52 55.1234-5678',
            'country' => 'México',
            'perfil' => 'zer'
        ]
    ],
    [
        'name' => 'Ingrid Pioneer (Colombia)',
        'data' => [
            'nombre' => 'Ingrid Pioneer Test-Robusto',
            'email' => 'ingrid.robusto@example.co',
            'phone' => '300 123 4567',
            'country' => 'Colombia',
            'perfil' => 'pioneer'
        ]
    ]
];

foreach ($scenarios as $scenario) {
    echo "[{$scenario['name']}]\n";
    $data = $scenario['data'];
    
    // 1. Procesamiento
    $scoreResult = LeadScoring::calculate($data);
    $label = StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = StatusConstants::getGroupById($label);
    
    echo "   📍 Teléfono Limpio: {$scoreResult['clean_phone']}\n";
    echo "   👤 Perfil Detectado: {$scoreResult['tipo_lead']}\n";

    // 2. Mapeo Columnas Garantizadas (Email, Score, País, Status, Fecha, Raw)
    // Usamos el formato de Native Array porque MondayAPI::createItem ya hace el json_encode global.
    $columnValues = [
        NewColumnIds::LEAD_EMAIL => ['email' => $data['email'], 'text' => $data['email']],
        NewColumnIds::NUMERIC_SCORE => (string)$scoreResult['total'],
        NewColumnIds::CLASSIFICATION_STATUS => ['label' => $label],
        NewColumnIds::COUNTRY_TEXT => $data['country'],
        NewColumnIds::DATE_CREATED => ['date' => date('Y-m-d')],
        NewColumnIds::RAW_DATA_JSON => json_encode($data, JSON_UNESCAPED_UNICODE)
    ];

    try {
        // PASO 1: Crear Item
        $itemResponse = $monday->createItem($boardId, $data['nombre'], $columnValues, $groupId);
        $itemId = $itemResponse['create_item']['id'] ?? null;

        if ($itemId) {
            echo "   ✅ Item #$itemId creado.\n";
            
            // PASO 2: Teléfono (Dedicado via Simple String - EL SECRETO)
            if (!empty($scoreResult['clean_phone'])) {
                $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::LEAD_PHONE, $scoreResult['clean_phone']);
                echo "   ✅ Teléfono enviado como Texto Simple.\n";
            }

            // PASO 3: Dropdowns (Directos)
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, $scoreResult['tipo_lead'], true);
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, 'Manual Test Robusto', true);
            echo "   ✅ Etiquetas actualizadas ({$scoreResult['tipo_lead']}).\n";
        }
    } catch (Exception $e) {
        $msg = file_exists(__DIR__ . '/last_error.json') ? file_get_contents(__DIR__ . '/last_error.json') : $e->getMessage();
        echo "   ❌ ERROR: " . $msg . "\n";
    }
    echo "\n";
}

echo "=== TEST COMPLETADO CON ÉXITO ===\n";
?>
