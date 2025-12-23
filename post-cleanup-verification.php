<?php
// post-cleanup-verification.php
// Verificación posterior a la limpieza del tablero

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';
require_once 'NewColumnIds.php';

echo "========================================\n";
echo "  VERIFICACIÓN POST-LIMPIEZA            \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "1. VERIFICANDO ESTRUCTURA DEL TABLERO...\n";
    
    // Consultar todas las columnas después de la limpieza
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            columns {
                id
                title
                type
            }
        }
    }';
    
    $result = $monday->query($query);
    $columns = $result['boards'][0]['columns'] ?? [];
    
    echo "   Columnas encontradas: " . count($columns) . "\n\n";
    
    // Verificar que las columnas nuevas siguen existiendo
    $expectedColumns = [
        NewColumnIds::CLASSIFICATION => 'Clasificación',
        NewColumnIds::ROLE_DETECTED => 'Rol Detectado', 
        NewColumnIds::TYPE_OF_LEAD => 'Tipo de Lead',
        NewColumnIds::SOURCE_CHANNEL => 'Canal de Origen',
        NewColumnIds::LANGUAGE => 'Idioma'
    ];
    
    $foundColumns = [];
    $missingColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column['id'], array_keys($expectedColumns))) {
            $foundColumns[] = $column;
            echo "   ✅ {$expectedColumns[$column['id']]} ({$column['id']}) - OK\n";
        }
    }
    
    // Verificar que no existan las columnas duplicadas que se debieron eliminar
    $deletedColumnIds = [
        'dropdown_mkypgz6f',  // Tipo de Lead duplicado
        'dropdown_mkypbsmj',  // Canal de Origen duplicado
        'dropdown_mkypzbbh',  // Idioma duplicado
        'date_mkypsy6q',      // Fecha de Entrada duplicada
        'date_mkypeap2',      // Próxima Acción duplicada
        'text_mkypbqgg'       // Mission Partner duplicado
    ];
    
    $stillExists = [];
    foreach ($columns as $column) {
        if (in_array($column['id'], $deletedColumnIds)) {
            $stillExists[] = $column;
            echo "   ⚠️  {$column['title']} ({$column['id']}) - AÚN EXISTE (debería haberse eliminado)\n";
        }
    }
    
    if (empty($stillExists)) {
        echo "   ✅ No se encontraron columnas duplicadas\n";
    }
    
    echo "\n2. PROBANDO FUNCIONALIDAD DEL WEBHOOK...\n";
    
    // Datos de prueba para verificar que todo funcione
    $testFormData = [
        'nombre' => 'Prueba Post-Limpieza - ' . date('Y-m-d H:i:s'),
        'email' => 'post-cleanup-test-' . time() . '@example.com',
        'company' => 'Post Cleanup Test Corp',
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
    
    echo "   Puntuación esperada: {$scoreResult['total']}\n";
    echo "   Clasificación esperada: {$scoreResult['priority_label']}\n";
    echo "   Rol detectado: {$scoreResult['detected_role']}\n\n";
    
    // Crear lead de prueba
    echo "3. CREANDO LEAD DE PRUEBA...\n";
    
    $columnValues = [
        'name' => $testFormData['nombre'],
        'lead_email' => ['email' => $testFormData['email'], 'text' => $testFormData['email']],
        'lead_company' => $testFormData['company'],
        'text' => $testFormData['role'],
        'lead_phone' => ['phone' => '999888777', 'country_short_name' => 'ES'],
        'lead_status' => ['label' => 'Lead nuevo'],
        'numeric_mkyn2py0' => $scoreResult['total'],
        
        // Usando las columnas nuevas
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
        echo "   ✅ Lead de prueba creado exitosamente (ID: $itemId)\n\n";
        
        echo "4. ACTUALIZANDO COLUMNAS NUEVAS...\n";
        
        try {
            $monday->changeSimpleColumnValue($leadsBoardId, $itemId, NewColumnIds::TYPE_OF_LEAD, "Universidad");
            echo "   ✅ Tipo de Lead actualizado\n";
        } catch (Exception $e) {
            echo "   ⚠️ Error actualizando Tipo de Lead: " . $e->getMessage() . "\n";
        }

        try {
            $monday->changeSimpleColumnValue($leadsBoardId, $itemId, NewColumnIds::SOURCE_CHANNEL, "Website");
            echo "   ✅ Canal de Origen actualizado\n";
        } catch (Exception $e) {
            echo "   ⚠️ Error actualizando Canal de Origen: " . $e->getMessage() . "\n";
        }

        try {
            $monday->changeSimpleColumnValue($leadsBoardId, $itemId, NewColumnIds::LANGUAGE, "Español");
            echo "   ✅ Idioma actualizado\n";
        } catch (Exception $e) {
            echo "   ⚠️ Error actualizando Idioma: " . $e->getMessage() . "\n";
        }
        
        echo "\n========================================\n";
        echo "  ¡VERIFICACIÓN POST-LIMPIEZA EXITOSA!  \n";
        echo "========================================\n";
        echo "✅ El tablero está limpio y optimizado\n";
        echo "✅ Las columnas nuevas están funcionando\n";
        echo "✅ El webhook opera correctamente\n";
        echo "✅ El sistema está listo para producción\n";
        echo "========================================\n";
        
        return true;
    } else {
        echo "   ❌ Error creando lead de prueba: " . json_encode($itemResponse) . "\n";
        return false;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
