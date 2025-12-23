<?php
// test-all-forms.php
// Simulates submissions from various CF7 forms to test the webhook handler by including it directly.

echo "========================================\n";
echo "  INICIANDO TEST DE SIMULACIÓN DE FORMS (LOCAL)  \n";
echo "========================================\n\n";

$testCases = [
    [
        'title' => 'Test 1: Mission Partner/Pioneer',
        'data' => [
            'perfil' => 'pioneer',
            'contact_name' => 'Adelino de Almeida',
            'email' => 'pioneer@example.com',
            'pais_cf7' => 'Portugal',
            'phone' => '123456789'
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
            'pais_otro' => 'México'
        ]
    ],
    [
        'title' => 'Test 3: Registro Ciudad (Grande)',
        'data' => [
            'perfil' => 'ciudad',
            'your-name' => 'Alcalde Juan Pérez',
            'your-email' => 'alcalde@ciudadgrande.gov',
            'poblacion' => '500000',
            'pais_cf7' => 'Colombia'
        ]
    ],
    [
        'title' => 'Test 4: Empresa (con Donación)',
        'data' => [
            'perfil' => 'empresa',
            'company' => 'Innovate Corp',
            'contact_name' => 'Laura Smith',
            'email' => 'laura.s@innovate.com',
            'modality' => 'Donación'
        ]
    ],
    [
        'title' => 'Test 5: Contacto General (Cold Lead)',
        'data' => [
            'perfil' => 'general',
            'your-name' => 'Carlos Diaz',
            'your-email' => 'carlos.d@email.com',
            'pais_cf7' => 'Argentina'
        ]
    ],
    [
        'title' => 'Test 6: Mentor',
        'data' => [
            'perfil' => 'mentor',
            'nombre' => 'Profesor Ricardo',
            'email' => 'ricardo.teacher@school.org',
            'pais_cf7' => 'Chile'
        ]
    ]
];

foreach ($testCases as $index => $test) {
    echo "--- Ejecutando: {$test['title']} ---\n";

    // Simulate the POST request by setting $_POST and $_SERVER variables
    $_POST = $test['data'];
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Capture the output of the webhook handler
    ob_start();
    include 'webhook-handler.php';
    $response = ob_get_clean();
    
    // The handler outputs headers and a JSON body. We only want the JSON body.
    // A simple way to find the start of the JSON body
    $json_start = strpos($response, '{');
    if ($json_start !== false) {
        $json_response_str = substr($response, $json_start);
        $jsonResponse = json_decode($json_response_str, true);
        echo "  -> Respuesta del Webhook:\n";
        echo json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n\n";
    } else {
        echo "  -> No se recibió una respuesta JSON válida.\n";
        echo "  -> Respuesta cruda: {$response}\n\n";
    }
}

echo "========================================\n";
echo "        FIN DE LA SIMULACIÓN          \n";
echo "========================================\n";

?>
