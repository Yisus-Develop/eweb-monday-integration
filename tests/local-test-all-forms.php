<?php
// local-test-all-forms.php
// Script local para probar la lógica de scoring sin depender de la API de Monday.com

require_once 'LeadScoring.php';

echo "========================================\n";
echo "  TEST LOCAL COMPLETO DE SCORING (Sin API)  \n";
echo "========================================\n\n";

$testCases = [
    [
        'title' => 'Test 1: Mission Partner/Pioneer (VIP)',
        'data' => [
            'perfil' => 'pioneer',
            'contact_name' => 'Adelino de Almeida',
            'email' => 'pioneer@example.com',
            'pais_cf7' => 'Portugal',
            'phone' => '123456789',
            'country' => 'Portugal'
        ]
    ],
    [
        'title' => 'Test 2: Registro Institución (Universidad Grande)',
        'data' => [
            'perfil' => 'institucion',
            'nombre' => 'Dra. Ana Miller',
            'ea_email' => 'rectora@universidadgrande.edu',
            'tipo_institucion' => 'Universidad',
            'numero_estudiantes' => '5000',
            'pais_otro' => 'México',
            'country' => 'México'
        ]
    ],
    [
        'title' => 'Test 3: Registro Ciudad (Grande)',
        'data' => [
            'perfil' => 'ciudad',
            'your-name' => 'Alcalde Juan Pérez',
            'your-email' => 'alcalde@ciudadgrande.gov',
            'poblacion' => '500000',
            'pais_cf7' => 'Colombia',
            'country' => 'Colombia'
        ]
    ],
    [
        'title' => 'Test 4: Empresa (con Donación)',
        'data' => [
            'perfil' => 'empresa',
            'company' => 'Innovate Corp',
            'contact_name' => 'Laura Smith',
            'email' => 'laura.s@innovate.com',
            'modality' => 'Donación',
            'country' => 'Estados Unidos'
        ]
    ],
    [
        'title' => 'Test 5: Contacto General (Cold Lead)',
        'data' => [
            'perfil' => 'general',
            'your-name' => 'Carlos Diaz',
            'your-email' => 'carlos.d@email.com',
            'pais_cf7' => 'Argentina',
            'country' => 'Argentina'
        ]
    ],
    [
        'title' => 'Test 6: Mentor',
        'data' => [
            'perfil' => 'mentor',
            'nombre' => 'Profesor Ricardo',
            'email' => 'ricardo.teacher@school.org',
            'pais_cf7' => 'Chile',
            'country' => 'Chile'
        ]
    ],
    [
        'title' => 'Test 7: País prioritario',
        'data' => [
            'perfil' => 'pais',
            'nombre' => 'Representante Colombia',
            'email' => 'rep@colombia.org',
            'pais_cf7' => 'Colombia',
            'country' => 'Colombia'
        ]
    ],
    [
        'title' => 'Test 8: Formulario con idioma especificado',
        'data' => [
            'perfil' => 'institucion',
            'nombre' => 'Escola Brasileira',
            'email' => 'contato@escola.br',
            'pais_cf7' => 'Brasil',
            'country' => 'Brasil',
            'ea_lang' => 'Português'
        ]
    ]
];

$allTestsPassed = true;

foreach ($testCases as $index => $test) {
    echo "--- Ejecutando: {$test['title']} ---\n";
    
    try {
        // Procesar los datos para mapearlos al formato esperado
        $processedData = [
            'name' => $test['data']['nombre'] ?? $test['data']['contact_name'] ?? $test['data']['your-name'] ?? 'Sin Nombre',
            'email' => $test['data']['email'] ?? $test['data']['ea_email'] ?? $test['data']['your-email'] ?? '',
            'phone' => $test['data']['phone'] ?? $test['data']['your-phone'] ?? '',
            'company' => $test['data']['org_name'] ?? $test['data']['company'] ?? $test['data']['entity'] ?? $test['data']['institucion'] ?? $test['data']['ea_institution'] ?? '',
            'role' => $test['data']['tipo_institucion'] ?? $test['data']['sector'] ?? $test['data']['interes'] ?? $test['data']['especialidad'] ?? $test['data']['ea_role'] ?? '',
            'country' => $test['data']['country'] ?? $test['data']['pais_cf7'] ?? $test['data']['pais_otro'] ?? $test['data']['ea_country'] ?? '',
            'city' => $test['data']['ciudad_cf7'] ?? $test['data']['ea_city'] ?? '',
            'perfil' => $test['data']['perfil'] ?? 'general',
            'tipo_institucion' => $test['data']['tipo_institucion'] ?? '',
            'numero_estudiantes' => (int)($test['data']['numero_estudiantes'] ?? 0),
            'poblacion' => (int)($test['data']['poblacion'] ?? 0),
            'modality' => $test['data']['modality'] ?? '',
            'ea_source' => $test['data']['ea_source'] ?? null,
            'ea_lang' => $test['data']['ea_lang'] ?? null,
        ];

        // Calcular el scoring
        $scoreResult = LeadScoring::calculate($processedData);

        echo "  -> Puntuación Total: {$scoreResult['total']}\n";
        echo "  -> Clasificación: {$scoreResult['priority_label']}\n";
        echo "  -> Rol Detectado: {$scoreResult['detected_role']}\n";
        echo "  -> Tipo de Lead: {$scoreResult['tipo_lead']}\n";
        echo "  -> Canal de Origen: {$scoreResult['canal_origen']}\n";
        echo "  -> Idioma: {$scoreResult['idioma']}\n";
        
        // Verificar si la puntuación y clasificación tienen sentido
        $expectedClassification = 'COLD'; // Default
        
        if ($processedData['perfil'] === 'pioneer' || $processedData['perfil'] === 'institucion' || $processedData['perfil'] === 'ciudad') {
            // Estos perfiles tienen 10 pts base, podría ser WARM o HOT
            $expectedClassification = $scoreResult['total'] > 20 ? 'HOT' : 'WARM';
        } elseif ($processedData['perfil'] === 'empresa' && $processedData['modality'] === 'Donación') {
            // Empresa con donación tiene pts extras
            $expectedClassification = $scoreResult['total'] >= 10 ? 'WARM' : 'COLD';
        } elseif ($processedData['perfil'] === 'mentor' || $processedData['perfil'] === 'zer') {
            // Mentor y Zer son COLD o WARM si tienen otros factores
            $expectedClassification = $scoreResult['total'] >= 10 ? 'WARM' : 'COLD';
        }
        
        if ($scoreResult['priority_label'] === $expectedClassification) {
            echo "  -> ✅ Clasificación correcta según perfil (esperada: $expectedClassification)\n";
        } else {
            echo "  -> ❌ Clasificación inesperada (esperada: $expectedClassification, obtenida: {$scoreResult['priority_label']})\n";
            $allTestsPassed = false;
        }

        // Mostrar desglose del scoring
        echo "  -> Desglose: " . json_encode($scoreResult['breakdown'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
    } catch (Exception $e) {
        echo "  -> ❌ Error: " . $e->getMessage() . "\n\n";
        $allTestsPassed = false;
    }
}

echo "========================================\n";
if ($allTestsPassed) {
    echo "✅ ¡TODOS LOS TESTS PASARON CORRECTAMENTE!\n";
    echo "La lógica de scoring está funcionando correctamente.\n";
    echo "Ahora se puede proceder con el testing real contra la API de Monday.com\n";
    echo "(una vez que las etiquetas de clasificación estén configuradas manualmente)\n";
} else {
    echo "❌ ALGUNOS TESTS FALLARON\n";
    echo "Revisar la lógica de scoring o los datos de prueba.\n";
}
echo "========================================\n";

?>
