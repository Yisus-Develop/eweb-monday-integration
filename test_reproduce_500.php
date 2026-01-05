<?php
// test_webhook_500.php
// Simula una petición al webhook para ver el error 500

$url = 'http://localhost/projects/monday-automation/src/wordpress/final-webhook-handler.php'; // Cambiar si es necesario

$testData = [
    'nombre' => 'Mars Challenge «Profesor X»', // Debería limpiarse a "Profesor X"
    'email' => 'mentor_test_' . time() . '@test.com',
    'telefono' => '+54 11 1234 5678',
    'pais_cf7' => 'Argentina',
    'perfil' => 'mentor',
    'ea_lang' => 'Español'
];

$_POST = $testData;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Simulando ejecución de final-webhook-handler.php...\n";
include 'deployment/webhook-handler.php';
echo "\n--- Fin del Test ---\n";

// Note: I can't reach localhost directly if it's not running
// Actually, I can just include the file in a script to see errors.

?>
<?php
// Alternativa:// Simular el input body
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'perfil' => 'zer',
    'next_step' => 'confirmacion',
    'monto' => '29',
    'pais_cf7' => 'CO',
    'ciudad_cf7' => 'medellin',
    'nombre' => 'Lead de Prueba Real',
    'fecha_nacimiento' => '1997-09-18',
    'institucion' => 'virtual',
    'email' => 'jrodriguez@virtualeduca.org',
    'pais_ui' => 'CO',
    'pais_otro' => '',
    'ciudad_ui' => 'medellin'
];
$data = $_POST; // For local use if needed

// Simular el input body
// PHP can't easily overwrite php://input, but we can simulate the handler's logic.

echo "Simulando ejecución de final-webhook-handler.php...\n";

// Mocking required files or just letting it run
try {
    // Definir constantes que estarían en config.php si falla el require
    // Pero vamos a intentar que el require funcione fijando el CWD o el path.
    
    ob_start();
    include 'webhook-handler.php';
    $output = ob_get_clean();
    
    echo "SALIDA DEL WEBHOOK (Fixed Handlers):\n";
    echo $output . "\n";
    
} catch (Throwable $t) {
    echo "FATAL ERROR / THROWABLE CATCHED:\n";
    echo $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine() . "\n";
}
