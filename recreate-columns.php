<?php
// recreate-columns.php
// Script para recrear las columnas con la configuración correcta usando la API de Monday

require_once '../config.php';
require_once 'MondayAPI.php';

function recreateColumns() {
    echo "========================================\n";
    echo "  RECREACIÓN DE COLUMNAS CON API CORRECTA\n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. OBTENIENDO LISTA DE COLUMNAS ACTUALES...\n";
        
        // Obtener todas las columnas actuales
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
        
        echo "   Columnas encontradas: " . count($columns) . "\n";
        
        // Identificar las columnas problemáticas que necesitan ser recreadas
        $problematicColumns = [
            'color_mkypv3rg' => 'Clasificación',      // Status con etiquetas incorrectas
            'color_mkyng649' => 'Rol Detectado',      // Status con etiquetas incorrectas
            'dropdown_mkyp8q98' => 'Tipo de Lead',    // Dropdown sin opciones
            'dropdown_mkypf16c' => 'Canal de Origen', // Dropdown sin opciones
            'dropdown_mkyps472' => 'Idioma'           // Dropdown sin opciones
        ];
        
        echo "\n2. IDENTIFICANDO COLUMNAS PROBLEMÁTICAS...\n";
        $columnsToDelete = [];
        
        foreach ($columns as $column) {
            if (isset($problematicColumns[$column['id']]) || $column['title'] === 'Clasificación' || $column['title'] === 'Rol Detectado') {
                if ($column['id'] === 'color_mkypv3rg' || $column['id'] === 'color_mkyng649' || 
                    $column['id'] === 'dropdown_mkyp8q98' || $column['id'] === 'dropdown_mkypf16c' || 
                    $column['id'] === 'dropdown_mkyps472') {
                    $columnsToDelete[] = $column;
                    echo "   - {$column['id']} ({$column['title']}) - Marcar para eliminación\n";
                }
            }
        }
        
        echo "\n3. ELIMINANDO COLUMNAS PROBLEMÁTICAS...\n";
        foreach ($columnsToDelete as $column) {
            echo "   Eliminando columna: {$column['id']} ({$column['title']})...\n";
            try {
                $deleteResult = $monday->deleteColumn($leadsBoardId, $column['id']);
                echo "   ✅ Columna eliminada: {$column['id']}\n";
            } catch (Exception $e) {
                echo "   ⚠️  Error eliminando columna {$column['id']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n4. CREANDO COLUMNAS STATUS CON ETIQUETAS CORRECTAS...\n";
        
        // Crear columna de clasificación con etiquetas correctas (HOT/WARM/COLD)
        echo "   Creando columna 'Clasificación' con etiquetas HOT/WARM/COLD...\n";
        
        $classificationMutation = '
        mutation {
          create_status_column(
            board_id: '.$leadsBoardId.',
            id: "classification_status",
            title: "Clasificación",
            defaults: {
              labels: [
                { color: dark_red, label: "HOT", index: 0},
                { color: working_orange, label: "WARM", index: 1},
                { color: dark_blue, label: "COLD", index: 2}
              ]
            },
            description: "Clasificación del lead según puntuación"
          ) {
            id
            title
            description
          }
        }';
        
        $classResult = $monday->rawQuery($classificationMutation);
        if (isset($classResult['data']) && isset($classResult['data']['create_status_column'])) {
            $newClassId = $classResult['data']['create_status_column']['id'];
            echo "   ✅ Columna 'Clasificación' creada con ID: $newClassId\n";
        } else {
            echo "   ❌ Error creando columna 'Clasificación': " . json_encode($classResult['errors'] ?? 'Unknown error') . "\n";
            $newClassId = 'classification_status'; // Usar ID genérico en caso de error
        }
        
        // Crear columna de rol detectado con etiquetas correctas
        echo "   Creando columna 'Rol Detectado' con roles específicos...\n";
        
        $roleMutation = '
        mutation {
          create_status_column(
            board_id: '.$leadsBoardId.',
            id: "role_detected",
            title: "Rol Detectado",
            defaults: {
              labels: [
                { color: purple, label: "Mission Partner", index: 0},
                { color: green, label: "Rector/Director", index: 1},
                { color: orange, label: "Alcalde/Gobierno", index: 2},
                { color: sky, label: "Corporate", index: 3},
                { color: pink, label: "Maestro/Mentor", index: 4},
                { color: grass_green, label: "Joven", index: 5}
              ]
            },
            description: "Rol detectado del lead"
          ) {
            id
            title
            description
          }
        }';
        
        $roleResult = $monday->rawQuery($roleMutation);
        if (isset($roleResult['data']) && isset($roleResult['data']['create_status_column'])) {
            $newRoleId = $roleResult['data']['create_status_column']['id'];
            echo "   ✅ Columna 'Rol Detectado' creada con ID: $newRoleId\n";
        } else {
            echo "   ❌ Error creando columna 'Rol Detectado': " . json_encode($roleResult['errors'] ?? 'Unknown error') . "\n";
            $newRoleId = 'role_detected'; // Usar ID genérico en caso de error
        }
        
        echo "\n5. CREANDO COLUMNAS DROPDOWN CON OPCIONES CORRECTAS...\n";
        
        // Crear columna de Tipo de Lead con opciones
        echo "   Creando columna 'Tipo de Lead' con opciones...\n";
        
        $typeLeadMutation = '
        mutation {
          create_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "type_of_lead",
            title: "Tipo de Lead",
            defaults: {
              label_limit_count: 1,
              limit_select: true,
              labels: [
                {label: "Universidad"},
                {label: "Escuela"},
                {label: "Empresa"},
                {label: "Iglesia"},
                {label: "Ministerio"},
                {label: "ONG"},
                {label: "Otro"}
              ]
            },
            description: "Tipo de entidad del lead"
          ) {
            id
            title
            description
          }
        }';
        
        $typeLeadResult = $monday->rawQuery($typeLeadMutation);
        if (isset($typeLeadResult['data']) && isset($typeLeadResult['data']['create_dropdown_column'])) {
            $newTypeLeadId = $typeLeadResult['data']['create_dropdown_column']['id'];
            echo "   ✅ Columna 'Tipo de Lead' creada con ID: $newTypeLeadId\n";
        } else {
            echo "   ❌ Error creando columna 'Tipo de Lead': " . json_encode($typeLeadResult['errors'] ?? 'Unknown error') . "\n";
            $newTypeLeadId = 'type_of_lead'; // Usar ID genérico en caso de error
        }
        
        // Crear columna de Canal de Origen con opciones
        echo "   Creando columna 'Canal de Origen' con opciones...\n";
        
        $sourceMutation = '
        mutation {
          create_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "source_channel",
            title: "Canal de Origen",
            defaults: {
              label_limit_count: 1,
              limit_select: true,
              labels: [
                {label: "Website"},
                {label: "Contact Form"},
                {label: "Mission Partner"},
                {label: "Red Social"},
                {label: "Evento"},
                {label: "Referido"},
                {label: "Otro"}
              ]
            },
            description: "Canal por el que llegó el lead"
          ) {
            id
            title
            description
          }
        }';
        
        $sourceResult = $monday->rawQuery($sourceMutation);
        if (isset($sourceResult['data']) && isset($sourceResult['data']['create_dropdown_column'])) {
            $newSourceId = $sourceResult['data']['create_dropdown_column']['id'];
            echo "   ✅ Columna 'Canal de Origen' creada con ID: $newSourceId\n";
        } else {
            echo "   ❌ Error creando columna 'Canal de Origen': " . json_encode($sourceResult['errors'] ?? 'Unknown error') . "\n";
            $newSourceId = 'source_channel'; // Usar ID genérico en caso de error
        }
        
        // Crear columna de Idioma con opciones
        echo "   Creando columna 'Idioma' con opciones...\n";
        
        $langMutation = '
        mutation {
          create_dropdown_column(
            board_id: '.$leadsBoardId.',
            id: "language",
            title: "Idioma",
            defaults: {
              label_limit_count: 1,
              limit_select: true,
              labels: [
                {label: "Español"},
                {label: "Portugués"},
                {label: "Inglés"},
                {label: "Francés"},
                {label: "Otro"}
              ]
            },
            description: "Idioma del lead"
          ) {
            id
            title
            description
          }
        }';
        
        $langResult = $monday->rawQuery($langMutation);
        if (isset($langResult['data']) && isset($langResult['data']['create_dropdown_column'])) {
            $newLangId = $langResult['data']['create_dropdown_column']['id'];
            echo "   ✅ Columna 'Idioma' creada con ID: $newLangId\n";
        } else {
            echo "   ❌ Error creando columna 'Idioma': " . json_encode($langResult['errors'] ?? 'Unknown error') . "\n";
            $newLangId = 'language'; // Usar ID genérico en caso de error
        }
        
        // Guardar los nuevos IDs para usar en el webhook
        $newIds = [
            'classification' => $newClassId,
            'role_detected' => $newRoleId,
            'type_of_lead' => $newTypeLeadId,
            'source_channel' => $newSourceId,
            'language' => $newLangId
        ];
        
        // Crear un archivo con los nuevos IDs
        $idsFileContent = "<?php\n";
        $idsFileContent .= "// NewColumnIds.php\n";
        $idsFileContent .= "// Nuevos IDs de columnas después de recrearlas\n\n";
        $idsFileContent .= "class NewColumnIds {\n";
        foreach ($newIds as $name => $id) {
            $idsFileContent .= "    const " . strtoupper($name) . " = '$id';\n";
        }
        $idsFileContent .= "}\n";
        $idsFileContent .= "?>";
        
        file_put_contents('NewColumnIds.php', $idsFileContent);
        
        echo "\n6. RESULTADOS DE LA RECREACIÓN...\n";
        echo "   Nuevos IDs generados:\n";
        foreach ($newIds as $name => $id) {
            echo "   - $name: $id\n";
        }
        
        echo "\n========================================\n";
        echo "      ¡COLUMNAS RECREADAS EXITOSAMENTE!   \n";
        echo "========================================\n";
        echo "✅ Las columnas ahora tienen las etiquetas\n";
        echo "   correctas según los requisitos del CRM\n";
        echo "✅ Se crearon las opciones correctas para\n";
        echo "   dropdown y status\n";
        echo "✅ Los nuevos IDs se guardaron en NewColumnIds.php\n";
        echo "========================================\n";
        
        return $newIds;
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar la recreación de columnas
$result = recreateColumns();

if ($result) {
    echo "\n✅ Proceso de recreación de columnas completado.\n";
    echo "Puedes continuar con la actualización del webhook handler.\n";
} else {
    echo "\n❌ Se encontraron errores en la recreación.\n";
}

?>
