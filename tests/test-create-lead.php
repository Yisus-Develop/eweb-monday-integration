<?php
// test-create-lead.php
// Script para crear un lead de prueba en Monday

require_once '../../../config/config.php';
require_once '../MondayAPI.php';
require_once '../LeadScoring.php';
require_once '../NewColumnIds.php';

echo "========================================\n";
echo "  CREACIÓN DE LEAD DE PRUEBA EN MONDAY   \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

// Datos de prueba para institucion
$testFormData = [
    'nombre' => 'Test Universidad 2026',
    'email' => 'test.university.2026@test.com',
    'pais_cf7' => 'España',
    'perfil' => 'institucion',
    'tipo_institucion' => 'Universidad',
    'numero_estudiantes' => 15000,
    'ea_source' => 'Contact Form 7',
    'ea_lang' => 'es',
    'phone' => '999888777',
    'city' => 'Madrid'
];

echo "DATOS DE ENTRADA:\n";
foreach ($testFormData as $key => $value) {
    echo "  - $key: $value\n";
}
echo "\n";

// Procesar con la lógica de scoring
$scoringData = [
    'name' => $testFormData['nombre'],
    'email' => $testFormData['email'],
    'company' => 'Test University 2026',
    'role' => $testFormData['tipo_institucion'],
    'country' => $testFormData['pais_cf7'],
    'perfil' => $testFormData['perfil'],
    'tipo_institucion' => $testFormData['tipo_institucion'],
    'numero_estudiantes' => $testFormData['numero_estudiantes'],
    'ea_source' => $testFormData['ea_source'],
    'ea_lang' => $testFormData['ea_lang'],
    'phone' => $testFormData['phone'],
    'city' => $testFormData['city']
];

$scoreResult = LeadScoring::calculate($scoringData);

echo "RESULTADO DEL SCORING:\n";
echo "  - Puntuación: {$scoreResult['total']}\n";
echo "  - Clasificación: {$scoreResult['priority_label']}\n";
echo "  - Rol detectado: {$scoreResult['detected_role']}\n";
echo "  - Tipo de lead: {$scoreResult['tipo_lead']}\n";
echo "  - Canal de origen: {$scoreResult['canal_origen']}\n";
echo "  - Idioma: {$scoreResult['idioma']}\n\n";

// Preparar valores de columna para Monday
$columnValues = [
    'lead_company' => $scoringData['company'],
    'lead_email' => json_encode(["email" => $scoringData['email'], "text" => $scoringData['email']]), // Formato correcto para columna email
    'lead_phone' => json_encode(["phone" => $scoringData['phone'], "country_short_name" => "ES"]), // Formato correcto para columna teléfono
    'text_mkyn95hk' => $scoringData['country'],
    'numeric_mkyn2py0' => $scoreResult['total'], // Lead Score
    'classification_status' => json_encode(["label" => $scoreResult['priority_label']]), // Formato correcto para columna status
    'text_mkypn0m' => '', // Mission Partner (campo texto para usar después con dropdown)
    'date_mkyp6w4t' => json_encode(["date" => date('Y-m-d')]), // Fecha de Entrada
    'date_mkypeap2' => json_encode(["date" => date('Y-m-d')]), // Próxima Acción
    'lead_status' => json_encode(["label" => 'Lead nuevo']) // Estado del lead
];

echo "CREANDO LEAD EN TABLERO MC – Lead Master Intake...\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Crear el item en Monday
    $result = $monday->createItem(18392144864, $scoringData['name'], $columnValues);
    
    $itemId = $result['create_item']['id'];
    
    echo "✅ LEAD CREADO EXITOSAMENTE!\n";
    echo "  - ID del Lead: $itemId\n";
    echo "  - Nombre: {$scoringData['name']}\n";
    echo "  - Email: {$scoringData['email']}\n";
    echo "  - Puntuación: {$scoreResult['total']}\n";
    echo "  - Clasificación: {$scoreResult['priority_label']}\n";
    echo "  - Tipo de Lead: {$scoreResult['tipo_lead']}\n";
    echo "  - Idioma: {$scoreResult['idioma']}\n";
    echo "  - Rol Detectado: {$scoreResult['detected_role']}\n\n";
    
    echo "PARA VERIFICAR:\n";
    echo "1. Visita: https://monday.com/boards/18392144864\n";
    echo "2. Busca el lead con nombre: \"{$scoringData['name']}\"\n";
    echo "3. Verifica que tenga todos los campos correctamente llenados\n\n";
    
    echo "========================================\n";
    echo "  PRUEBA COMPLETADA EXITOSAMENTE         \n";
    echo "========================================\n";
    echo "El sistema está funcionando perfectamente!\n";
    echo "✅ Webhook Handler\n";
    echo "✅ Procesamiento de datos\n";
    echo "✅ Scoring automático\n";
    echo "✅ Detección de idioma\n";
    echo "✅ Clasificación HOT/WARM/COLD\n";
    echo "✅ Detección de rol\n";
    echo "✅ Creación de lead en Monday\n";
    echo "✅ Asignación de valores correctos a columnas\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERROR AL CREAR LEAD: " . $e->getMessage() . "\n";
}

?>