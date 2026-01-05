<?php
// final_syntax_check.php
echo "Iniciando chequeo de sintaxis...\n";

// Definir constantes necesarias para que no fallen los includes
if (!defined('MONDAY_API_TOKEN')) define('MONDAY_API_TOKEN', 'test');
if (!defined('MONDAY_BOARD_ID')) define('MONDAY_BOARD_ID', '123');
if (!defined('WEBHOOK_DEBUG')) define('WEBHOOK_DEBUG', true);

$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock de POST data
$_POST = [
    'nombre' => 'Mars Challenge «Tester Final»',
    'email' => 'final@test.com'
];

try {
    // Solo check de sintaxis y carga de clases
    require_once 'deployment/NewColumnIds.php';
    require_once 'deployment/StatusConstants.php';
    require_once 'deployment/MondayAPI.php';
    require_once 'deployment/LeadScoring.php';
    
    echo "✅ Clases cargadas correctamente.\n";
    
    // Verificamos el archivo principal (usando php -l)
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
