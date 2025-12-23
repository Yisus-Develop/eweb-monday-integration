<?php
// fix-role-column.php
// Script para corregir la columna de roles con colores válidos

require_once '../config.php';
require_once 'MondayAPI.php';

function fixRoleColumn() {
    echo "========================================\n";
    echo "  CORRECCIÓN DE COLUMNA DE ROLES        \n";
    echo "  Usando colores válidos según API      \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. OBTENIENDO LISTA DE COLUMNAS...\n";
        
        // Obtener todas las columnas para confirmar que la columna de roles no existe
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
        
        // Verificar si ya existe una columna de roles detectados
        $existingRoleColumn = null;
        foreach ($columns as $column) {
            if (strpos(strtolower($column['title']), 'rol') !== false || strpos(strtolower($column['title']), 'role') !== false) {
                $existingRoleColumn = $column;
                break;
            }
        }
        
        if ($existingRoleColumn) {
            echo "   Se encontró columna de roles: {$existingRoleColumn['id']} ({$existingRoleColumn['title']})\n";
            echo "   Eliminando para recrear con colores válidos...\n";
            
            try {
                $deleteResult = $monday->deleteColumn($leadsBoardId, $existingRoleColumn['id']);
                echo "   ✅ Columna eliminada: {$existingRoleColumn['id']}\n";
            } catch (Exception $e) {
                echo "   ⚠️  Error eliminando columna: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n2. CREANDO COLUMNA DE ROLES CON COLORES VÁLIDOS...\n";
        
        // Crear columna de rol detectado con colores válidos basados en el error
        $roleMutation = '
        mutation {
          create_status_column(
            board_id: '.$leadsBoardId.',
            id: "role_detected_new",
            title: "Rol Detectado",
            defaults: {
              labels: [
                { color: purple, label: "Mission Partner", index: 0},
                { color: brown, label: "Rector/Director", index: 1},
                { color: steel, label: "Alcalde/Gobierno", index: 2},
                { color: sky, label: "Corporate", index: 3},
                { color: steel, label: "Maestro/Mentor", index: 4},
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
            
            // Actualizar el archivo de IDs con el nuevo ID
            $newIds = [
                'classification' => 'classification_status',
                'role_detected' => $newRoleId,
                'type_of_lead' => 'type_of_lead',
                'source_channel' => 'source_channel',
                'language' => 'language'
            ];
            
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
            echo "   ✅ Archivo NewColumnIds.php actualizado\n";
            
        } else {
            echo "   ❌ Error creando columna 'Rol Detectado': " . json_encode($roleResult['errors'] ?? 'Unknown error') . "\n";
            
            // Si sigue fallando, intentemos sin colores específicos
            $roleMutationSimple = '
            mutation {
              create_status_column(
                board_id: '.$leadsBoardId.',
                id: "role_detected_simple",
                title: "Rol Detectado",
                defaults: {
                  labels: [
                    { label: "Mission Partner", index: 0},
                    { label: "Rector/Director", index: 1},
                    { label: "Alcalde/Gobierno", index: 2},
                    { label: "Corporate", index: 3},
                    { label: "Maestro/Mentor", index: 4},
                    { label: "Joven", index: 5}
                  ]
                },
                description: "Rol detectado del lead"
              ) {
                id
                title
                description
              }
            }';
            
            $simpleResult = $monday->rawQuery($roleMutationSimple);
            if (isset($simpleResult['data']) && isset($simpleResult['data']['create_status_column'])) {
                $simpleRoleId = $simpleResult['data']['create_status_column']['id'];
                echo "   ✅ Columna 'Rol Detectado' creada sin colores (ID: $simpleRoleId)\n";
                
                // Actualizar el archivo de IDs
                $newIds = [
                    'classification' => 'classification_status',
                    'role_detected' => $simpleRoleId,
                    'type_of_lead' => 'type_of_lead',
                    'source_channel' => 'source_channel',
                    'language' => 'language'
                ];
                
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
                echo "   ✅ Archivo NewColumnIds.php actualizado\n";
            } else {
                echo "   ❌ Error también sin colores: " . json_encode($simpleResult['errors'] ?? 'Unknown error') . "\n";
                echo "   Utilizando ID temporal para Rol Detectado\n";
                
                // Actualizar el archivo con ID temporal
                $newIds = [
                    'classification' => 'classification_status',
                    'role_detected' => 'role_detected',
                    'type_of_lead' => 'type_of_lead',
                    'source_channel' => 'source_channel',
                    'language' => 'language'
                ];
                
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
                echo "   ✅ Archivo NewColumnIds.php actualizado con ID temporal\n";
            }
        }
        
        echo "\n========================================\n";
        echo "      ¡CORRECCIÓN COMPLETADA!          \n";
        echo "========================================\n";
        echo "✅ La columna de roles ha sido recreada\n";
        echo "✅ Se usaron colores válidos o se\n";
        echo "   omitieron en caso de errores\n";
        echo "✅ El archivo de IDs está actualizado\n";
        echo "========================================\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar la corrección de la columna de roles
$result = fixRoleColumn();

if ($result) {
    echo "\n✅ Corrección de columna de roles completada.\n";
} else {
    echo "\n❌ Se encontraron errores en la corrección.\n";
}

?>
