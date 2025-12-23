<?php
// test-language-detection.php
// Prueba la detección dinámica de idiomas

require_once 'LeadScoring.php';

echo "=== TEST: Detección Dinámica de Idiomas ===\n\n";

$testCases = [
    // Español
    ['country' => 'México', 'expected' => 'Español'],
    ['country' => 'España', 'expected' => 'Español'],
    ['country' => 'Colombia', 'expected' => 'Español'],
    
    // Portugués
    ['country' => 'Brasil', 'expected' => 'Portugués'],
    ['country' => 'Portugal', 'expected' => 'Portugués'],
    
    // Inglés
    ['country' => 'Estados Unidos', 'expected' => 'Inglés'],
    ['country' => 'United States', 'expected' => 'Inglés'],
    ['country' => 'USA', 'expected' => 'Inglés'],
    ['country' => 'United Kingdom', 'expected' => 'Inglés'],
    ['country' => 'Canada', 'expected' => 'Inglés'],
    
    // Francés
    ['country' => 'Francia', 'expected' => 'Francés'],
    ['country' => 'France', 'expected' => 'Francés'],
    
    // Alemán
    ['country' => 'Alemania', 'expected' => 'Alemán'],
    ['country' => 'Germany', 'expected' => 'Alemán'],
    
    // Italiano
    ['country' => 'Italia', 'expected' => 'Italiano'],
    
    // País no configurado (debe retornar default)
    ['country' => 'Japón', 'expected' => 'Español'],
    
    // Sin país (debe retornar default)
    ['country' => '', 'expected' => 'Español'],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    $data = [
        'country' => $test['country'],
        'perfil' => 'general'
    ];
    
    $result = LeadScoring::calculate($data);
    $detected = $result['idioma'];
    
    $status = ($detected === $test['expected']) ? '✅ PASS' : '❌ FAIL';
    
    if ($detected === $test['expected']) {
        $passed++;
    } else {
        $failed++;
    }
    
    echo sprintf(
        "%s | País: %-20s | Esperado: %-10s | Detectado: %-10s\n",
        $status,
        $test['country'] ?: '(vacío)',
        $test['expected'],
        $detected
    );
}

echo "\n=== RESUMEN ===\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 ¡Todos los tests pasaron!\n";
} else {
    echo "\n⚠️ Algunos tests fallaron. Revisar configuración.\n";
}
