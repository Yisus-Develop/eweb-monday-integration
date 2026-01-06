<?php
// inspect-wp.php
// Script para inspeccionar formularios de Contact Form 7 vía API de WordPress

$site_url = 'https://marschallenge.space';
$username = 'wmaster_cs4or9qs';
$app_password = 'THuf tLn6 MQXG bCsS usyN yBca';

// Limpiar espacios de la contraseña
$app_password = str_replace(' ', '', $app_password);

echo "--- Conectando a WordPress API ($site_url) ---\n";

$auth = base64_encode("$username:$app_password");

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic $auth\r\n" .
                    "Content-Type: application/json\r\n"
    ]
]);

// 1. Listar Formularios
$endpoint = "$site_url/wp-json/contact-form-7/v1/contact-forms";
echo "Consultando: $endpoint\n";

$response = @file_get_contents($endpoint, false, $context);

if ($response === false) {
    die("❌ Error conectando a la API. Verifica credenciales o si el plugin CF7 REST API está activo.\n");
}

$forms = json_decode($response, true);

echo "✅ Conexión Exitosa. Formularios encontrados: " . count($forms) . "\n\n";

foreach ($forms as $form) {
    echo "📄 ID: {$form['id']} | Título: {$form['title']}\n";
    // Si la API expone propiedades del formulario, intentamos mostrarlas
    // Nota: La API estándar de CF7 a veces requiere autenticación extra para ver campos
    // Intentaremos hacer un fetch del detalle del formulario
    
    $detailUrl = "$endpoint/{$form['id']}";
    $detailResponse = @file_get_contents($detailUrl, false, $context);
    
    if ($detailResponse) {
        $detail = json_decode($detailResponse, true);
        if (isset($detail['properties']['form']) && is_string($detail['properties']['form'])) {
            echo "   Campos detectados (análisis simple del template):\n";
            preg_match_all('/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)/', $detail['properties']['form'], $matches);
            
            if (!empty($matches[2])) {
                foreach ($matches[2] as $idx => $fieldName) {
                    $type = $matches[1][$idx];
                    echo "   - $fieldName ($type)\n";
                }
            }
        } else {
            echo "   (No se pudo analizar el template del formulario - Propiedad 'form' vacía o no accesible)\n";
        }
    }
    echo "---------------------------------------------------\n";
}
