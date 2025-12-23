<?php
// test-v4-hybrid.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/NewColumnIds.php';
require_once __DIR__ . '/StatusConstants.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

echo "=== TEST HÍBRIDO FINAL V4 ===\n\n";

$scenarios = [
    [
        'name' => 'Luis Zer Test (Terminology + Hybrid JSON)',
        'data' => [
            'nombre' => 'Luis Zer Hybrid-V4',
            'email' => 'luis.hybrid@example.mx',
            'phone' => '+52 55.1234-5678',
            'country' => 'México',
            'perfil' => 'zer'
        ]
    ],
    [
        'name' => 'Ingrid Pioneer Test (Terminology + Hybrid JSON)',
        'data' => [
            'nombre' => 'Ingrid Pioneer Hybrid-V4',
            'email' => 'ingrid.hybrid@example.co',
            'phone' => '300 123 4567',
            'country' => 'Colombia',
            'perfil' => 'pioneer'
        ]
    ]
];

foreach ($scenarios as $scenario) {
    echo "[{$scenario['name']}]\n";
    $data = $scenario['data'];
    $scoreResult = LeadScoring::calculate($data);
    
    // Mapeo Híbrido: Columnas complejas van como String JSON dentro del objeto
    $columnValues = [
        NewColumnIds::LEAD_EMAIL => json_encode(['email' => $data['email'], 'text' => $data['email']]),
        NewColumnIds::NUMERIC_SCORE => $scoreResult['total'],
        NewColumnIds::CLASSIFICATION_STATUS => ['label' => StatusConstants::getScoreClassification($scoreResult['total'])],
        NewColumnIds::COUNTRY_TEXT => $data['country'],
        NewColumnIds::DATE_CREATED => ['date' => date('Y-m-d')],
        NewColumnIds::RAW_DATA_JSON => json_encode($data, JSON_UNESCAPED_UNICODE)
    ];

    try {
        // PASO 1: Crear Item
        $itemResponse = $monday->createItem($boardId, $data['nombre'], $columnValues);
        $itemId = $itemResponse['create_item']['id'] ?? null;

        if ($itemId) {
            echo "   ✅ Item #$itemId creado.\n";
            
            // PASO 2: Teléfono (Formato de 'change' es diferente a 'create')
            $phoneVal = [
                'phone' => $scoreResult['clean_phone'], 
                'country_short_name' => $scoreResult['country_iso'] ?: 'ES'
            ];
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LEAD_PHONE, $phoneVal);
            echo "   ✅ Teléfono enviado.\n";

            // PASO 3: Dropdowns (Directos)
            $monday->changeSimpleColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, $scoreResult['tipo_lead'], true);
            echo "   ✅ Dropdown 'Tipo de Lead' actualizado a: {$scoreResult['tipo_lead']}\n";
        }
    } catch (Exception $e) {
        $realError = file_exists(__DIR__ . '/last_error.json') ? file_get_contents(__DIR__ . '/last_error.json') : $e->getMessage();
        echo "   ❌ ERROR: " . $realError . "\n";
    }
    echo "\n";
}

echo "=== TEST COMPLETADO ===\n";
?>
