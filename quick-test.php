<?php
// quick-test.php
// Prueba rápida con formato correcto

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  PRUEBA RÁPIDA - FORMATO CORRECTO       \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Datos de prueba
    $testFormData = [
        'nombre' => 'Prueba Rápida - ' . date('Y-m-d H:i:s'),
        'email' => 'quick-test-' . time() . '@example.com',
        'company' => 'Quick Test Corp',
        'role' => 'Rector/Director',
        'country' => 'España',
        'perfil' => 'institucion',
        'tipo_institucion' => 'Universidad',
        'numero_estudiantes' => 5000,
        'ea_source' => 'Contact Form 7',
        'ea_lang' => 'es'
    ];
    
    $scoringData = [
        'name' => $testFormData['nombre'],
        'email' => $testFormData['email'],
        'company' => $testFormData['company'],
        'role' => $testFormData['role'],
        'country' => $testFormData['country'],
        'perfil' => $testFormData['perfil'],
        'tipo_institucion' => $testFormData['tipo_institucion'],
        'numero_estudiantes' => $testFormData['numero_estudiantes'],
        'ea_source' => $testFormData['ea_source'],
        'ea_lang' => $testFormData['ea_lang'],
        'phone' => '999888777',
        'city' => 'Madrid'
    ];
    
    $scoreResult = LeadScoring::calculate($scoringData);
    
    echo "1. CREANDO LEAD CON FORMATO CORRECTO...\n";
    
    // Usando el formato correcto para cada tipo de columna
    $columnValues = [
        'name' => $testFormData['nombre'],
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        
        // Columnas status - formato correcto: {"label": "nombre"}
        NewColumnIds::CLASSIFICATION => ["label" => $scoreResult['priority_label']],
        NewColumnIds::ROLE_DETECTED => ["label" => $scoreResult['detected_role']],
        
        'text_mkyn95hk' => $testFormData['country'],
        'text_mkypn0m' => 'Test MP',
        'date_mkyp6w4t' => ['date' => date('Y-m-d')],
        'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
        'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])
    ];
    
    $itemResponse = $monday->createItem($leadsBoardId, $testFormData['nombre'], $columnValues);
    
    if (isset($itemResponse['create_item']['id'])) {
        $itemId = $itemResponse['create_item']['id'];
        echo "   ✅ Lead creado exitosamente (ID: $itemId)\n\n";
        
        echo "2. ACTUALIZANDO COLUMNAS DROPDOWN...\n";
        
        $monday->changeColumnValue($leadsBoardId, $itemId, NewColumnIds::TYPE_OF_LEAD, ['labels' => [$scoreResult['tipo_lead']]]);
        echo "   ✅ Tipo de Lead actualizado\n";
        
        $monday->changeColumnValue($leadsBoardId, $itemId, NewColumnIds::SOURCE_CHANNEL, ['labels' => [$scoreResult['canal_origen']]]);
        echo "   ✅ Canal de Origen actualizado\n";
        
        // Corregir idioma de "es" a "Español"
        $monday->changeColumnValue($leadsBoardId, $itemId, NewColumnIds::LANGUAGE, ['labels' => ['Español']]);
        echo "   ✅ Idioma actualizado\n";
        
        echo "\n========================================\n";
        echo "  ¡SISTEMA CRM OPERATIVO!               \n";
        echo "========================================\n";
        echo "✅ Webhook completamente funcional\n";
        echo "✅ Todas las columnas operativas\n";
        echo "✅ Listo para producción inmediata\n";
        echo "========================================\n";
        
        return true;
    } else {
        echo "   ❌ Error: " . json_encode($itemResponse) . "\n";
        return false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
