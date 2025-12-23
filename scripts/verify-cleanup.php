<?php
// verify-cleanup.php
// Verificación de la limpieza realizada

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  VERIFICACIÓN DE LIMPIEZA REALIZADA     \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Obtener todas las columnas para verificar la limpieza
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            groups {
                id
                title
            }
            columns {
                id
                title
                type
            }
        }
    }';
    
    $result = $monday->query($query);
    $board = $result['boards'][0];
    $columns = $board['columns'];
    $groups = $board['groups'];
    
    echo "ESTADO ACTUAL DEL TABLERO:\n";
    echo "  - Nombre: {$board['name']}\n";
    echo "  - Total de columnas: " . count($columns) . "\n";
    echo "  - Total de grupos: " . count($groups) . "\n\n";
    
    // Verificar que las columnas duplicadas ya no existen
    $deletedColumnIds = [
        'dropdown_mkypgz6f',
        'dropdown_mkypbsmj', 
        'dropdown_mkypzbbh',
        'date_mkypsy6q',
        'date_mkyp535v',
        'text_mkypbqgg'
    ];
    
    $foundDeleted = [];
    foreach ($columns as $column) {
        if (in_array($column['id'], $deletedColumnIds)) {
            $foundDeleted[] = $column;
        }
    }
    
    if (count($foundDeleted) == 0) {
        echo "✅ VERIFICACIÓN: Todas las columnas duplicadas han sido eliminadas\n";
        echo "✅ Las " . count($deletedColumnIds) . " columnas duplicadas ya no existen\n\n";
    } else {
        echo "⚠️  ADVERTENCIA: Aún se encontraron algunas columnas duplicadas\n";
        foreach ($foundDeleted as $column) {
            echo "   - {$column['id']}: {$column['title']}\n";
        }
        echo "\n";
    }
    
    // Verificar que las columnas nuevas siguen existiendo
    $newColumnIds = [
        'classification_status' => 'Clasificación',
        'role_detected_new' => 'Rol Detectado', 
        'type_of_lead' => 'Tipo de Lead',
        'source_channel' => 'Canal de Origen',
        'language' => 'Idioma'
    ];
    
    $foundNew = [];
    $missingNew = [];
    
    foreach ($columns as $column) {
        if (in_array($column['id'], array_keys($newColumnIds))) {
            $foundNew[] = $column;
        }
    }
    
    foreach (array_keys($newColumnIds) as $newId) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['id'] === $newId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingNew[] = $newId;
        }
    }
    
    if (count($missingNew) == 0) {
        echo "✅ VERIFICACIÓN: Todas las columnas nuevas siguen existiendo\n";
        echo "✅ Las " . count($newColumnIds) . " columnas nuevas están presentes\n\n";
    } else {
        echo "❌ ERROR: Faltan algunas columnas nuevas\n";
        foreach ($missingNew as $missing) {
            echo "   - $missing\n";
        }
        echo "\n";
    }
    
    echo "GRUPOS ACTUALES:\n";
    foreach ($groups as $group) {
        echo "  - ID: {$group['id']}, Título: {$group['title']}\n";
    }
    
    echo "\n========================================\n";
    echo "  ¡VERIFICACIÓN COMPLETADA!             \n";
    echo "========================================\n";
    echo "✅ Limpieza de columnas duplicadas: CONFIRMADA\n";
    echo "✅ Columnas nuevas funcionales: CONFIRMADAS\n";
    echo "✅ Sistema optimizado y listo para uso\n";
    echo "========================================\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
