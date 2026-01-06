<?php
// Script de prueba local para simular un envío de formulario
// Uso: php test-simulation.php

require_once 'LeadScoring.php';
// require_once 'MondayAPI.php'; // Descomentar cuando tengamos credenciales reales

echo "--- Iniciando Simulación de Lead Scoring ---\n";

// Caso de prueba 1: Lead "PERFECTO"
$payloadHot = [
    'name' => 'Juan Pérez',
    'email' => 'juan@universidad.edu',
    'role' => 'Rector Magnífico',
    'country' => 'España',
    'phone' => '+34600000000',
    'mission_partner' => 'Partner X'
];

echo "\n[TEST 1] Payload HOT:\n";
print_r($payloadHot);

$resultHot = LeadScoring::calculate($payloadHot);
echo "Resultado:\n";
print_r($resultHot);

// Validación
if ($resultHot['total'] >= 25 && $resultHot['priority_label'] === 'HOT') {
    echo "✅ TEST 1 PASADO: Lead fue identificado como HOT correctamente.\n";
} else {
    echo "❌ TEST 1 FALLADO: Lead debería ser HOT.\n";
}

// Caso de prueba 2: Lead "BÁSICO"
$payloadCold = [
    'name' => 'Ana Garcia',
    'email' => 'ana@gmail.com',
    'role' => 'Estudiante',
    'country' => 'Noruega', // No prioritario
    'phone' => '', // Incompleto
    'mission_partner' => ''
];

echo "\n[TEST 2] Payload COLD:\n";
print_r($payloadCold);

$resultCold = LeadScoring::calculate($payloadCold);
echo "Resultado:\n";
print_r($resultCold);

if ($resultCold['total'] < 10 && $resultCold['priority_label'] === 'COLD') {
    echo "✅ TEST 2 PASADO: Lead fue identificado como COLD correctamente.\n";
} else {
    echo "❌ TEST 2 FALLADO: Lead debería ser COLD.\n";
}

echo "\n--- Fin de la Simulación ---\n";
