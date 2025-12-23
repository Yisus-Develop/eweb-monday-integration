<?php
// test-scoring-only.php
// Prueba SOLO el scoring sin enviar a Monday

require_once 'LeadScoring.php';

echo "=== TEST DE LEAD SCORING (6 Formularios) ===\n\n";

$testCases = [
    [
        'title' => '🔥 Test 1: Mission Partner/Pioneer',
        'expected_score' => 10,
        'expected_class' => 'WARM',
        'data' => [
            'perfil' => 'pioneer',
            'name' => 'Adelino de Almeida',
            'email' => 'pioneer@example.com',
            'country' => 'Portugal',
            'phone' => '123456789'
        ]
    ],
    [
        'title' => '🔥 Test 2: Rector Universidad Grande + País Prioritario',
        'expected_score' => 23,
        'expected_class' => 'HOT',
        'data' => [
            'perfil' => 'institucion',
            'name' => 'Dra. Ana Miller',
            'email' => 'rectora@universidad.edu',
            'tipo_institucion' => 'Universidad',
            'numero_estudiantes' => 5000,
            'country' => 'México',
            'phone' => '555-1234'
        ]
    ],
    [
        'title' => '🔥 Test 3: Alcalde Ciudad Grande + País Prioritario',
        'expected_score' => 21,
        'expected_class' => 'HOT',
        'data' => [
            'perfil' => 'ciudad',
            'name' => 'Alcalde Juan Pérez',
            'email' => 'alcalde@ciudad.gov',
            'poblacion' => 500000,
            'country' => 'Colombia',
            'phone' => '555-5678'
        ]
    ],
    [
        'title' => '🟡 Test 4: Empresa con Donación',
        'expected_score' => 11,
        'expected_class' => 'WARM',
        'data' => [
            'perfil' => 'empresa',
            'name' => 'Laura Smith',
            'email' => 'laura@innovate.com',
            'company' => 'Innovate Corp',
            'modality' => 'Donación',
            'country' => 'España',
            'phone' => '555-9999'
        ]
    ],
    [
        'title' => '🔵 Test 5: Contacto General (Cold)',
        'expected_score' => 3,
        'expected_class' => 'COLD',
        'data' => [
            'perfil' => 'general',
            'name' => 'Carlos Diaz',
            'email' => 'carlos@email.com',
            'country' => 'Argentina',
            'phone' => '555-0000'
        ]
    ],
    [
        'title' => '🔵 Test 6: Mentor',
        'expected_score' => 8,
        'expected_class' => 'COLD',
        'data' => [
            'perfil' => 'mentor',
            'name' => 'Profesor Ricardo',
            'email' => 'ricardo@school.org',
            'country' => 'Chile',
            'phone' => '555-1111'
        ]
    ],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    echo "--- {$test['title']} ---\n";
    
    $result = LeadScoring::calculate($test['data']);
    
    $scoreMatch = ($result['total'] == $test['expected_score']);
    $classMatch = ($result['priority_label'] == $test['expected_class']);
    
    $status = ($scoreMatch && $classMatch) ? '✅ PASS' : '❌ FAIL';
    
    if ($scoreMatch && $classMatch) {
        $passed++;
    } else {
        $failed++;
    }
    
    echo "  Nombre: {$test['data']['name']}\n";
    echo "  Email: {$test['data']['email']}\n";
    echo "  País: {$test['data']['country']}\n";
    echo "  Score: {$result['total']} (esperado: {$test['expected_score']}) " . ($scoreMatch ? '✅' : '❌') . "\n";
    echo "  Clasificación: {$result['priority_label']} (esperado: {$test['expected_class']}) " . ($classMatch ? '✅' : '❌') . "\n";
    echo "  Tipo Lead: {$result['tipo_lead']}\n";
    echo "  Rol: {$result['detected_role']}\n";
    echo "  Canal: {$result['canal_origen']}\n";
    echo "  Idioma: {$result['idioma']}\n";
    echo "  Desglose: " . json_encode($result['breakdown']) . "\n";
    echo "  $status\n\n";
}

echo "=== RESUMEN ===\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 ¡Todos los tests de scoring pasaron!\n";
    echo "✅ FASE 6.1: Test de Scoring - COMPLETADA\n";
} else {
    echo "\n⚠️ Algunos tests fallaron. Revisar lógica de scoring.\n";
}
