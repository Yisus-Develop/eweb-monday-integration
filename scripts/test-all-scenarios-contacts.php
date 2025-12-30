<?php
// test-all-scenarios-contacts.php
// Este script simula todas las posibilidades de entrada para verificar el funcionamiento del tablero de Contactos.

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../MondayAPI.php';
require_once __DIR__ . '/../LeadScoring.php';
require_once __DIR__ . '/../NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$scenarios = [
    'HOT_MISSION_PARTNER' => [
        'nombre' => 'Test HOT Mission Partner',
        'email' => 'partner@test.com',
        'telefono' => '+34611111111',
        'perfil' => 'pioneer',
        'pais_cf7' => 'España',
        'ea_city' => 'Barcelona',
        'monto' => 5000,
        'tipo_institucion' => 'Corporativo'
    ],
    'WARM_RECTOR' => [
        'nombre' => 'Test WARM Rector',
        'email' => 'rector@universidad_test.edu',
        'telefono' => '+34622222222',
        'perfil' => 'institucion',
        'pais_cf7' => 'Colombia',
        'ea_city' => 'Bogotá',
        'tipo_institucion' => 'Universidad'
    ],
    'COLD_GENERAL' => [
        'nombre' => 'Test COLD Estudiante',
        'email' => 'estudiante@test.com',
        'perfil' => 'general',
        'pais_cf7' => 'Otros'
    ],
    'WHATSAPP_SIMULATION' => [
        'nombre' => 'Test WhatsApp Lead',
        'email' => 'whatsapp_user@test.com',
        'telefono' => '+34633333333',
        'perfil' => 'empresa',
        'pais_cf7' => 'México',
        'ea_source' => 'whatsapp' // Simulación de origen
    ]
];

echo "=== INICIANDO PRUEBAS INTEGRALES EN TABLERO DE CONTACTOS ===\n\n";

foreach ($scenarios as $key => $data) {
    echo "--- Escenario: $key ---\n";

    // 1. Simular Mapeo de Webhook
    $scoringData = [
        'name' => $data['nombre'],
        'email' => $data['email'],
        'phone' => $data['telefono'] ?? '',
        'company' => $data['empresa'] ?? '',
        'role' => $data['perfil'],
        'country' => $data['pais_cf7'],
        'city' => $data['ea_city'] ?? '',
        'perfil' => $data['perfil'],
        'monto' => $data['monto'] ?? 0,
        'tipo_institucion' => $data['tipo_institucion'] ?? '',
        'ea_source' => $data['ea_source'] ?? 'form'
    ];

    // 2. Calcular Scoring
    $scoreResult = LeadScoring::calculate($scoringData);
    echo "   Score: {$scoreResult['total']} | Clasificación: {$scoreResult['priority_label']}\n";

    // 3. Preparar Columnas (usando NewColumnIds)
    $columnValues = [
        NewColumnIds::EMAIL => ['email' => $data['email'], 'text' => $data['email']],
        NewColumnIds::PHONE => ['phone' => $data['telefono'] ?? '', 'country_short_name' => 'ES'],
        NewColumnIds::PUESTO => $scoreResult['detected_role'],
        NewColumnIds::STATUS => ['label' => 'Lead'],
        NewColumnIds::LEAD_SCORE => $scoreResult['total'],
        NewColumnIds::CLASSIFICATION => ['label' => $scoreResult['priority_label']],
        NewColumnIds::ROLE_DETECTED => ['label' => $scoreResult['detected_role']],
        NewColumnIds::COUNTRY => $data['pais_cf7'],
        NewColumnIds::CITY => $scoringData['city'],
        NewColumnIds::AMOUNT => $scoringData['monto'],
        NewColumnIds::INST_TYPE => ['label' => $scoringData['tipo_institucion'] ?: 'En curso'],
        NewColumnIds::ENTRY_DATE => ['date' => date('Y-m-d')],
        NewColumnIds::NEXT_ACTION => ['date' => date('Y-m-d')],
        NewColumnIds::COMMENTS => ['text' => "RESUMEN DE PRUEBA:\n" . implode("\n", array_map(
            function($k, $v) { return "- " . ucfirst(str_replace(['_', '-'], ' ', $k)) . ": " . (is_array($v) ? json_encode($v) : $v); },
            array_keys($data),
            array_values($data)
        ))]
    ];

    try {
        // 4. Crear Item
        $itemResponse = $monday->createItem($boardId, $data['nombre'] . " (TEST)", $columnValues);
        $itemId = $itemResponse['create_item']['id'];
        echo "   ✅ Item creado: $itemId\n";

        // 5. Actualizar Dropdowns
        echo "   Actualizando dropdowns...\n";
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
        
        $canal = ($key === 'WHATSAPP_SIMULATION') ? 'WhatsApp' : 'Website';
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => [$canal]]);
        
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['labels' => [$scoreResult['idioma']]]);
        
        echo "   ✅ Dropdowns actualizados.\n";

    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== PRUEBA DE DUPLICADO ===\n";
echo "Simulando re-envío de 'partner@test.com'...\n";
// El mismo que el primero
$emailDuplicado = 'partner@test.com';
$existingItems = $monday->getItemsByColumnValue($boardId, NewColumnIds::EMAIL, $emailDuplicado);

if (!empty($existingItems)) {
    $itemId = $existingItems[0]['id'];
    echo "   ✅ Duplicado detectado (ID: $itemId). Actualizando...\n";
    $monday->updateItem($boardId, $itemId, [NewColumnIds::AMOUNT => 9999]);
    echo "   ✅ Monto actualizado a 9999.\n";
} else {
    echo "   ❌ No se encontró el duplicado (revisa la búsqueda por email).\n";
}

echo "\n=== PRUEBAS FINALIZADAS ===\n";
?>
