<?php
// quick-test-with-existing-labels.php
// Versión temporal del script que usa las etiquetas existentes para poder hacer la prueba completa

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';

function runQuickIntegrationTest() {
    echo "========================================\n";
    echo "  PRUEBA RÁPIDA CON ETIQUETAS EXISTENTES  \n";
    echo "========================================\n\n";

    // Validar configuración
    if (MONDAY_API_TOKEN === 'eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU5Nzk0MTA5NCwiYWFpIjoxMSwidWlkIjo5NzE1OTk2NCwiaWFkIjoiMjAyNS0xMi0xNVQxOToyODoyMy4zMDFaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzI4MDY0MjksInJnbiI6InVzZTEifQ.KrDtZiJSdyJECg8LrnuPntNVJhIZQu7aHU-XS4MChKo') {
        echo "⚠️  ADVERTENCIA: Token posiblemente expirado o inválido detectado\n\n";
    }

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        
        // Obtener todos los tableros en el workspace 13608938
        echo "🔍 Obteniendo tableros del workspace 13608938...\n";
        $query = '
        query {
            boards (workspace_ids: "13608938") {
                id
                name
                state
                permissions
            }
        }';
        
        $response = $monday->query($query);
        $boards = $response['boards'] ?? [];
        
        // Buscar específicamente el tablero Leads (ID correcto)
        $leadsBoardId = '18392144864';
        $boardFound = false;
        foreach ($boards as $board) {
            if ($board['id'] == $leadsBoardId) {
                $boardFound = true;
                break;
            }
        }
        
        if (!$boardFound) {
            echo "❌ No se encontró el tablero de Leads (18392144864) en el workspace.\n";
            return;
        }
        
        echo "🎯 Usando tablero de Leads: $leadsBoardId\n";
        
        // Datos para prueba
        $testData = [
            'nombre' => 'Prueba Integración ' . date('H:i:s'),
            'email' => 'test.' . time() . '@virtualdemo.com',
            'perfil' => 'empresa',
            'pais_otro' => 'España',
            'company' => 'Virtual Demo Corp',
            'telefono' => '123456789',
            'role' => 'Prueba de Integración',
            'tipo_institucion' => 'Corporación',
            'numero_estudiantes' => 0,
            'poblacion' => 0,
            'modality' => 'Donación'
        ];
        
        // Usar la lógica del webhook para procesar los datos
        $scoringData = [
            'name' => $testData['nombre'],
            'email' => $testData['email'],
            'phone' => $testData['telefono'],
            'company' => $testData['company'],
            'role' => $testData['role'],
            'country' => $testData['pais_otro'],
            'city' => '',
            'perfil' => $testData['perfil'],
            'tipo_institucion' => $testData['tipo_institucion'],
            'numero_estudiantes' => (int)$testData['numero_estudiantes'],
            'poblacion' => (int)$testData['poblacion'],
            'modality' => $testData['modality'],
            'ea_source' => null,
            'ea_lang' => null,
        ];
        
        $scoreResult = LeadScoring::calculate($scoringData);
        
        echo "\n📊 RESULTADOS DEL SCORING:\n";
        echo "   Puntuación Total: {$scoreResult['total']}\n";
        echo "   Clasificación Calculada: {$scoreResult['priority_label']} (pero se usará 'Listo' temporalmente)\n";
        echo "   Rol Detectado: {$scoreResult['detected_role']}\n";
        echo "   Tipo de Lead: {$scoreResult['tipo_lead']}\n";
        
        // Preparar valores de columna usando las etiquetas existentes temporalmente
        $columnValues = [
            'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
            'lead_company' => $scoringData['company'],
            'text' => $scoringData['role'],
            'lead_phone' => ['phone' => $scoringData['phone'], 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'], // Usando una etiqueta existente
            
            // Columnas de negocio (usando los IDs actualizados)
            'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score
            'color_mkypv3rg' => ['label' => 'Listo'],                                       // Temporalmente 'Listo' en lugar de clasificación real
            'color_mkyng649' => ['label' => $scoreResult['detected_role']],                 // Rol Detectado
            'text_mkyn95hk' => $scoringData['country'],                                     // País
            
            // Nuevas columnas
            'dropdown_mkyp8q98' => ['label' => $scoreResult['tipo_lead']],                  // Tipo de Lead
            'dropdown_mkypf16c' => ['label' => $scoreResult['canal_origen']],               // Canal de Origen
            'text_mkypn0m' => ($scoringData['perfil'] === 'pioneer') ? $scoringData['name'] : '', // Mission Partner
            'dropdown_mkyps472' => ['label' => $scoreResult['idioma']],                     // Idioma
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],                                   // Fecha de Entrada
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+2 days'))],             // Próxima Acción
            'long_text_mkypqppc' => json_encode($scoreResult['breakdown'])                  // Notas Internas
        ];
        
        // Intentar crear el ítem
        echo "\n📤 Enviando lead de prueba a Monday.com...\n";
        $itemResponse = $monday->createItem($leadsBoardId, $scoringData['name'], $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "✅ ¡Lead de prueba creado exitosamente!\n";
            echo "   ID del ítem: $itemId\n";
            echo "   Puntuación: {$scoreResult['total']}\n";
            echo "   Clasificación temporal: Listo (debe cambiarse a HOT/WARM/COLD manualmente)\n";
            echo "   Rol detectado: {$scoreResult['detected_role']}\n";
            
            echo "\n🔍 ¡PROCESO DE PRUEBA EXITOSO!\n";
            echo "   El sistema puede crear ítems en el tablero correctamente.\n";
            echo "   Solo falta configurar manualmente las etiquetas de clasificación:\n";
            echo "   - Ir a la columna 'Clasificación' en el tablero de Leads\n";
            echo "   - Configurar las etiquetas como: 'HOT', 'WARM', 'COLD'\n";
            echo "   - Luego podrás usar esas etiquetas en lugar de 'Listo'\n";
            
        } else {
            echo "❌ Error al crear el ítem: " . json_encode($itemResponse) . "\n";
        }
        
        echo "\n========================================\n";
        echo "        PRUEBA RÁPIDA COMPLETADA        \n";
        echo "========================================\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        if (strpos($e->getMessage(), 'invalid token') !== false) {
            echo "🔍 El token de API puede haber expirado o no ser válido\n";
        }
    }
}

// Ejecutar la prueba
runQuickIntegrationTest();
?>
