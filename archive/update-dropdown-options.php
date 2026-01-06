<?php
// update-dropdown-options.php
// Script para actualizar las columnas dropdown con opciones válidas

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  ACTUALIZACIÓN DE OPCIONES DROPDOWN     \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "1. IDENTIFICANDO COLUMNAS SIN OPCIONES...\n";
    echo "   - Tipo de Lead (dropdown_mkyp8q98): Configuración vacía\n";
    echo "   - Canal de Origen (dropdown_mkypf16c): Configuración vacía\n";
    echo "   - Idioma (dropdown_mkyps472): Configuración vacía\n\n";
    
    // Vamos a intentar crear las opciones necesarias para las columnas dropdown
    // usando la mutación create_column
    echo "2. CREANDO OPCIONES PARA COLUMNAS DROPDOWN...\n\n";
    
    // Para columnas dropdown que ya existen pero no tienen opciones definidas,
    // necesitamos un enfoque diferente. Vamos a comprobar si se pueden agregar opciones.
    
    // Intentemos primero crear las opciones para Tipo de Lead
    $typeLeadOptions = ['Universidad', 'Escuela', 'Empresa', 'Iglesia', 'Ministerio', 'ONG', 'Otro'];
    $sourceOptions = ['Website', 'Contact Form', 'Mission Partner', 'Red Social', 'Evento', 'Referido', 'Otro'];
    $languageOptions = ['Español', 'Portugués', 'Inglés', 'Francés', 'Otro'];
    
    // Intentemos actualizar las columnas dropdown con las opciones
    // Primero, necesitamos obtener la estructura actual de la columna
    
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            columns {
                id
                title
                type
                settings_str
            }
        }
    }';
    
    $result = $monday->query($query);
    $columns = $result['boards'][0]['columns'] ?? [];
    
    // Buscar la columna 'Tipo de Lead'
    $typeLeadColumn = null;
    foreach ($columns as $column) {
        if ($column['id'] === 'dropdown_mkyp8q98' && $column['title'] === 'Tipo de Lead') {
            $typeLeadColumn = $column;
            break;
        }
    }
    
    if ($typeLeadColumn) {
        echo "3. ENCONTRADA COLUMNA 'Tipo de Lead' ({$typeLeadColumn['id']})\n";
        echo "   Intentando crear opciones...\n\n";
        
        // Para actualizar una columna dropdown con nuevas opciones, 
        // necesitamos usar update_dropdown_column
        $updateTypeLead = '
        mutation {
          update_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "'.$typeLeadColumn['id'].'",
            settings: {
              labels: [
                { name: "Universidad" },
                { name: "Escuela" },
                { name: "Empresa" },
                { name: "Iglesia" },
                { name: "Ministerio" },
                { name: "ONG" },
                { name: "Otro" }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización para 'Tipo de Lead'...\n";
        $result = $monday->rawQuery($updateTypeLead);
        
        if (isset($result['data']) && isset($result['data']['update_dropdown_column'])) {
            echo "   ✅ Opciones de 'Tipo de Lead' actualizadas\n";
        } else {
            echo "   ❌ Error actualizando 'Tipo de Lead': " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            
            // Mostrar sugerencia para crear manualmente
            echo "   📝 NOTA: Esta actualización puede requerir hacerse manualmente en la interfaz de Monday\n";
        }
    } else {
        echo "   ❌ No se encontró la columna 'Tipo de Lead'\n";
    }
    
    // Repetir para Canal de Origen
    $sourceColumn = null;
    foreach ($columns as $column) {
        if ($column['id'] === 'dropdown_mkypf16c' && $column['title'] === 'Canal de Origen') {
            $sourceColumn = $column;
            break;
        }
    }
    
    if ($sourceColumn) {
        echo "4. ENCONTRADA COLUMNA 'Canal de Origen' ({$sourceColumn['id']})\n";
        echo "   Intentando crear opciones...\n\n";
        
        $updateSource = '
        mutation {
          update_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "'.$sourceColumn['id'].'",
            settings: {
              labels: [
                { name: "Website" },
                { name: "Contact Form" },
                { name: "Mission Partner" },
                { name: "Red Social" },
                { name: "Evento" },
                { name: "Referido" },
                { name: "Otro" }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización para 'Canal de Origen'...\n";
        $result = $monday->rawQuery($updateSource);
        
        if (isset($result['data']) && isset($result['data']['update_dropdown_column'])) {
            echo "   ✅ Opciones de 'Canal de Origen' actualizadas\n";
        } else {
            echo "   ❌ Error actualizando 'Canal de Origen': " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            echo "   📝 NOTA: Esta actualización puede requerir hacerse manualmente en la interfaz de Monday\n";
        }
    } else {
        echo "   ❌ No se encontró la columna 'Canal de Origen'\n";
    }
    
    // Repetir para Idioma
    $langColumn = null;
    foreach ($columns as $column) {
        if ($column['id'] === 'dropdown_mkyps472' && $column['title'] === 'Idioma') {
            $langColumn = $column;
            break;
        }
    }
    
    if ($langColumn) {
        echo "5. ENCONTRADA COLUMNA 'Idioma' ({$langColumn['id']})\n";
        echo "   Intentando crear opciones...\n\n";
        
        $updateLang = '
        mutation {
          update_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "'.$langColumn['id'].'",
            settings: {
              labels: [
                { name: "Español" },
                { name: "Portugués" },
                { name: "Inglés" },
                { name: "Francés" },
                { name: "Otro" }
              ]
            }
          ) {
            id
          }
        }';
        
        echo "   Enviando actualización para 'Idioma'...\n";
        $result = $monday->rawQuery($updateLang);
        
        if (isset($result['data']) && isset($result['data']['update_dropdown_column'])) {
            echo "   ✅ Opciones de 'Idioma' actualizadas\n";
        } else {
            echo "   ❌ Error actualizando 'Idioma': " . json_encode($result['errors'] ?? 'Unknown error') . "\n";
            echo "   📝 NOTA: Esta actualización puede requerir hacerse manualmente en la interfaz de Monday\n";
        }
    } else {
        echo "   ❌ No se encontró la columna 'Idioma'\n";
    }
    
    echo "\n========================================\n";
    echo "     RESUMEN DE ACTUALIZACIONES         \n";
    echo "========================================\n";
    echo "⚠️  Algunas actualizaciones pueden haber \n";
    echo "   fallado debido a restricciones de   \n";
    echo "   la API. Estas deben hacerse manual-  \n";
    echo "   mente en la interfaz de Monday.com   \n";
    echo "========================================\n";
    
    echo "\n6. CREANDO WEBHOOK TEMPORAL...\n";
    echo "   Creando archivo con valores por defecto para columnas sin opciones...\n";
    
    // Crear una versión del webhook handler que maneje las columnas sin opciones
    $newWebhookContent = file_get_contents('webhook-handler.php');
    
    // Reemplazo para manejar columnas dropdown sin opciones
    $updatedWebhookContent = str_replace(
        "'dropdown_mkyp8q98' => ['label' => \$scoreResult['tipo_lead']],                  // Tipo de Lead (NEW ID)",
        "// 'dropdown_mkyp8q98' => ['label' => \$scoreResult['tipo_lead']],                  // Tipo de Lead - TEMPORALMENTE DESACTIVADO - No tiene opciones",
        $newWebhookContent
    );
    
    $updatedWebhookContent = str_replace(
        "'dropdown_mkypf16c' => ['label' => \$scoreResult['canal_origen']],               // Canal de Origen (NEW ID)",
        "// 'dropdown_mkypf16c' => ['label' => \$scoreResult['canal_origen']],               // Canal de Origen - TEMPORALMENTE DESACTIVADO - No tiene opciones",
        $updatedWebhookContent
    );
    
    $updatedWebhookContent = str_replace(
        "'dropdown_mkyps472' => ['label' => \$scoreResult['idioma']],                     // Idioma (NEW ID)",
        "// 'dropdown_mkyps472' => ['label' => \$scoreResult['idioma']],                     // Idioma - TEMPORALMENTE DESACTIVADO - No tiene opciones",
        $updatedWebhookContent
    );
    
    // Agregar versiones con texto en su lugar
    $updatedWebhookContent = str_replace(
        "// Nuevas columnas críticas (IDs de Fase 2.1)",
        "// Nuevas columnas críticas (IDs de Fase 2.1)
        'text_mkyp8q98' => \$scoreResult['tipo_lead'],                                    // Tipo de Lead (como texto temporal, antes era dropdown)
        'text_mkypf16c' => \$scoreResult['canal_origen'],                                 // Canal de Origen (como texto temporal, antes era dropdown)  
        'text_mkyps472' => \$scoreResult['idioma'],                                       // Idioma (como texto temporal, antes era dropdown)",
        $updatedWebhookContent
    );
    
    file_put_contents('webhook-handler-updated.php', $updatedWebhookContent);
    
    echo "   ✅ Archivo webhook-handler-updated.php creado con manejo de columnas sin opciones\n";
    echo "   ⚠️  Este archivo temporalmente usa columnas de texto en lugar de dropdown\n\n";
    
    echo "========================================\n";
    echo "      ACCIONES RECOMENDADAS             \n";
    echo "========================================\n";
    echo "1. Actualizar manualmente las columnas dropdown en Monday.com:\n";
    echo "   - Tipo de Lead: Universidad, Escuela, Empresa, etc.\n";
    echo "   - Canal de Origen: Website, Contact Form, etc.\n";
    echo "   - Idioma: Español, Portugués, Inglés, etc.\n";
    echo "2. Usar webhook-handler-updated.php temporalmente\n";
    echo "3. Volver al webhook-handler.php original después de crear las opciones\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>
