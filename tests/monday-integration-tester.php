<?php
// monday-integration-tester.php
// Script para probar la integración completa con Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';
require_once 'LeadScoring.php';

function runIntegrationTest() {
    echo "========================================\n";
    echo "  INICIANDO PRUEBA DE INTEGRACIÓN COMPLETA  \n";
    echo "========================================\n\n";

    // Validar configuración
    if (MONDAY_API_TOKEN === 'YOUR_ACTUAL_MONDAY_API_TOKEN_HERE') {
        echo "❌ ERROR: Debes configurar un token real de Monday.com en config.php\n";
        return;
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
        
        echo "📊 Tableros encontrados en el workspace: " . count($boards) . "\n";
        foreach ($boards as $board) {
            echo "  - ID: {$board['id']}, Nombre: {$board['name']}, Estado: {$board['state']}\n";
        }
        
        // Buscar el tablero de Leads principal (ID específico: 18392144864) o buscar por nombre excluyendo subelementos
        $leadsBoardId = null;
        foreach ($boards as $board) {
            // Buscar específicamente por ID correcto
            if ($board['id'] == '18392144864') {
                $leadsBoardId = $board['id'];
                break;
            }
        }

        // Si no encontramos por ID, buscar por nombre excluyendo los subelementos
        if (!$leadsBoardId) {
            foreach ($boards as $board) {
                if (stripos($board['name'], 'leads') !== false && stripos($board['name'], 'subelementos') === false) {
                    $leadsBoardId = $board['id'];
                    break;
                }
            }
        }

        if (!$leadsBoardId) {
            echo "⚠️  No se encontró un tablero de Leads principal en el workspace.\n";
            echo "🔍 Tableros candidatos encontrados: ";
            foreach ($boards as $board) {
                if (stripos($board['name'], 'lead') !== false) {
                    echo "\n   - ID: {$board['id']}, Nombre: {$board['name']} (estado: {$board['state']})";
                }
            }
            echo "\n";
            return;
        }

        echo "\n🎯 Usando tablero de Leads: $leadsBoardId (Nombre: {$boards[array_search($leadsBoardId, array_column($boards, 'id'))]['name']})\n";
        
        // Probar la creación de un lead de prueba
        echo "\n🧪 Creando lead de prueba...\n";
        
        $testData = [
            'nombre' => 'Prueba Integración',
            'email' => 'test.integration@virtualdemo.com',
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
        
        // Preparar valores de columna (usando los IDs actuales del sistema)
        $columnValues = [
            'lead_email' => ['email' => $scoringData['email'], 'text' => $scoringData['email']],
            'lead_company' => $scoringData['company'],
            'text' => $scoringData['role'],
            'lead_phone' => ['phone' => '123456789', 'country_short_name' => 'ES'], // Formato para columna de teléfono
            
            // Columnas de negocio (usando los IDs actualizados)
            'numeric_mkyn2py0' => $scoreResult['total'],                                     // Lead Score
            'color_mkypv3rg' => ['label' => $scoreResult['priority_label']],                // Clasificación (HOT/WARM/COLD)
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
        echo "📤 Enviando lead de prueba a Monday.com...\n";
        $itemResponse = $monday->createItem($leadsBoardId, $scoringData['name'], $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "✅ ¡Lead de prueba creado exitosamente!\n";
            echo "   ID del ítem: $itemId\n";
            echo "   Puntuación: {$scoreResult['total']}\n";
            echo "   Clasificación: {$scoreResult['priority_label']}\n";
            echo "   Rol detectado: {$scoreResult['detected_role']}\n";
            
            // Verificar si las etiquetas de clasificación se establecieron correctamente
            // (esto dependerá de si las etiquetas HOT/WARM/COLD están configuradas en Monday.com)
            echo "\n🔍 Verificando configuración de columna de Clasificación...\n";
            echo "⚠️  IMPORTANTE: Las etiquetas de la columna 'Clasificación' deben estar \n";
            echo "   configuradas manualmente como 'HOT', 'WARM', 'COLD' en la interfaz de Monday.com\n";
            echo "   ya que la API no puede crear estas etiquetas automáticamente.\n";
            
        } else {
            echo "❌ Error al crear el ítem: " . json_encode($itemResponse) . "\n";
        }
        
        // Probar duplicado (segundo envío con mismo email)
        echo "\n🧪 Probando manejo de duplicados...\n";
        $existingItems = $monday->getItemsByColumnValue($leadsBoardId, 'lead_email', $scoringData['email']);
        
        if (!empty($existingItems)) {
            echo "✅ Duplicado detectado correctamente\n";
            $itemIdToUpdate = $existingItems[0]['id'];
            
            // Actualizar con nuevos datos
            $updatedColumnValues = $columnValues;
            $updatedColumnValues['text'] = 'Lead Actualizado - Prueba de Duplicado';
            
            $updateResponse = $monday->updateItem($leadsBoardId, $itemIdToUpdate, $updatedColumnValues);
            if (isset($updateResponse['change_column_value']['id'])) {
                echo "✅ Lead duplicado actualizado exitosamente\n";
            } else {
                echo "❌ Error al actualizar duplicado: " . json_encode($updateResponse) . "\n";
            }
        } else {
            echo "⚠️  No se encontraron duplicados para prueba\n";
        }
        
        echo "\n========================================\n";
        echo "        PRUEBA DE INTEGRACIÓN COMPLETADA        \n";
        echo "========================================\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        echo "🔍 Verifica que el token de Monday.com sea correcto y tengas permisos adecuados\n";
    }
}

// Ejecutar la prueba
runIntegrationTest();
?>
