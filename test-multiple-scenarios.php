<?php
// test-multiple-scenarios.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/StatusConstants.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$scenarios = [
    'HOT' => [
        'your-name' => 'Rector VIP Test',
        'your-email' => 'rector@universidad.edu',
        'your-phone' => '+34600000000',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 5000,
        'pais_cf7' => 'España',
        'ea_city' => 'Madrid'
    ],
    'WARM' => [
        'your-name' => 'Empresa Test',
        'your-email' => 'contacto@empresa.com',
        'your-phone' => '+34900000000',
        'perfil' => 'empresa',
        'company' => 'Innovate S.A.',
        'pais_cf7' => 'México',
        'ea_city' => 'CDMX'
    ],
    'COLD' => [
        'your-name' => 'Curioso Test',
        'your-email' => 'estudiante@gmail.com',
        'your-phone' => '',
        'perfil' => 'general',
        'pais_cf7' => 'Otros',
        'ea_city' => ''
    ]
];

echo "=== TEST DE MÚLTIPLES ESTADOS (HOT/WARM/COLD) ===\n\n";

foreach ($scenarios as $key => $data) {
    echo "[$key] Procesando...\n";
    
    // Simular el mismo flujo que el webhook real
    $scoringData = [
        'name' => $data['your-name'],
        'email' => $data['your-email'],
        'phone' => $data['your-phone'],
        'company' => $data['company'] ?? $data['org_name'] ?? $data['institucion'] ?? '',
        'role' => $data['ea_role'] ?? $data['tipo_institucion'] ?? '',
        'country' => $data['pais_cf7'],
        'perfil' => $data['perfil'],
        'numero_estudiantes' => $data['numero_estudiantes'] ?? 0
    ];

    $scoreResult = LeadScoring::calculate($scoringData);
    $label = StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = StatusConstants::getGroupById($label);
    
    echo "   Score: " . $scoreResult['total'] . " -> Etiqueta: $label (Grupo: $groupId)\n";

    $columnValues = [
        'lead_email' => ['email' => $data['your-email'], 'text' => $data['your-email']],
        'numeric_mkyn2py0' => $scoreResult['total'],
        'classification_status' => ['label' => $label],
        'text_mkyn95hk' => $data['pais_cf7'],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'], // Puesto
        'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'role_detected_new' => ['label' => $scoreResult['detected_role']],
        'date_mkyp6w4t' => ['date' => date('Y-m-d')], // Fecha Entrada
        'long_text_mkypqppc' => ['text' => "Simulación de $key: " . json_encode($scoreResult['breakdown'])]
    ];

    try {
        $response = $monday->createItem($boardId, $data['your-name'] . " (FULL TEST $key)", $columnValues, $groupId);
        $itemId = $response['create_item']['id'];
        echo "✅ Creado en Monday con ID: $itemId\n";
        
        // Actualizar Dropdowns (Tipo de Lead, Origen)
        echo "   Actualizando dropdowns...\n";
        try {
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
            echo "   ✅ Tipo de Lead actualizado ({$scoreResult['tipo_lead']})\n";
        } catch (Exception $e) {
            echo "   ❌ Error Tipo Lead: " . $e->getMessage() . "\n";
        }

        try {
            $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => ['Contact Form']]);
            echo "   ✅ Canal de Origen actualizado (Contact Form)\n";
        } catch (Exception $e) {
            echo "   ❌ Error Canal Origen: " . $e->getMessage() . "\n";
        }
        
        echo "✅ Completado $key.\n\n";
    } catch (Exception $e) {
        echo "❌ Error en $key: " . $e->getMessage() . "\n\n";
    }
}
