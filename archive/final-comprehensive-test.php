<?php
// final-comprehensive-test.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/LeadScoring.php';
require_once __DIR__ . '/StatusConstants.php';
require_once __DIR__ . '/NewColumnIds.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = MONDAY_BOARD_ID;

$scenarios = [
    'HOT' => [
        'your-name' => 'Dr. Alejandro Rector',
        'your-email' => 'rector@universidad-modelo.edu',
        'your-phone' => '+34611223344',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 8500,
        'pais_cf7' => 'España',
        'ea_city' => 'Barcelona',
        'ea_role' => 'Rector Magnífico'
    ],
    'WARM' => [
        'your-name' => 'Ing. Marta Sánchez',
        'your-email' => 'marta.sanchez@tech-corp.com',
        'your-phone' => '+525512345678',
        'perfil' => 'empresa',
        'company' => 'Tech Solutions Ltd',
        'pais_cf7' => 'México',
        'ea_city' => 'CDMX',
        'modality' => 'Donación'
    ],
    'COLD' => [
        'your-name' => 'Juan Estudiante',
        'your-email' => 'juan.estu@gmail.com',
        'your-phone' => '',
        'perfil' => 'general',
        'pais_cf7' => 'Otros',
        'ea_city' => '',
        'ea_role' => 'Estudiante'
    ],
    'SPAM' => [
        'your-name' => 'Jackpot Winner',
        'your-email' => 'win@10minutemail.com',
        'your-phone' => '000000000',
        'perfil' => 'general',
        'pais_cf7' => 'Otros'
    ]
];

echo "=== INICIANDO TEST FINAL COMPREHENSIVO ===\n\n";

foreach ($scenarios as $key => $data) {
    echo "[$key] Generando lead...\n";
    
    // Mapeo igual al del webhook real
    $scoringData = [
        'name' => $data['your-name'],
        'email' => $data['your-email'],
        'phone' => $data['your-phone'] ?? '',
        'company' => $data['company'] ?? $data['org_name'] ?? $data['institucion'] ?? '',
        'role' => $data['ea_role'] ?? $data['tipo_institucion'] ?? '',
        'country' => $data['pais_cf7'] ?? '',
        'city' => $data['ea_city'] ?? '',
        'perfil' => $data['perfil'] ?? 'general',
        'numero_estudiantes' => $data['numero_estudiantes'] ?? 0,
        'modality' => $data['modality'] ?? ''
    ];

    // Procesar con el cerebro (LeadScoring)
    $scoreResult = LeadScoring::calculate($scoringData);
    
    // Verificar si es Spam por el mail
    $isDisposable = false;
    $disposableDomains = ['tempmail.com', 'guerrillamail.com', '10minutemail.com', 'mailinator.com'];
    $emailDomain = substr(strrchr($scoringData['email'], "@"), 1);
    if (in_array($emailDomain, $disposableDomains)) $isDisposable = true;

    // Determinar etiqueta y grupo
    $label = $isDisposable ? 'No calificado' : StatusConstants::getScoreClassification($scoreResult['total']);
    $groupId = $isDisposable ? 'group_mkyph1ky' : StatusConstants::getGroupById($label);
    
    echo "   Score: " . $scoreResult['total'] . " -> Etiqueta: $label (Grupo: $groupId)\n";

    // Columnas completas
    $columnValues = [
        'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
        'numeric_mkyn2py0' => $scoreResult['total'],
        'classification_status' => ['label' => $isDisposable ? 'COLD' : $label],
        'text_mkyn95hk' => $scoringData['country'],
        'lead_company' => $scoringData['company'],
        'text' => $scoringData['role'],
        'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
        'lead_status' => ['label' => $isDisposable ? 'No calificado' : 'Lead nuevo'],
        'role_detected_new' => ['label' => $scoreResult['detected_role']],
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'long_text_mkypqppc' => ['text' => "TEST FINAL: " . json_encode($scoreResult['breakdown'])]
    ];

    try {
        $response = $monday->createItem($boardId, $scoringData['name'] . " (FINAL)", $columnValues, $groupId);
        $itemId = $response['create_item']['id'];
        
        // Dropdowns manuales (Tipo de Lead, Origen e Idioma)
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => ['Website']]);
        $monday->changeColumnValue($boardId, $itemId, NewColumnIds::LANGUAGE, ['labels' => [$scoreResult['idioma']]]);
        
        echo "   ✅ Creado con éxito. ID: $itemId\n\n";
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== TEST FINALIZADO ===\n";
?>
