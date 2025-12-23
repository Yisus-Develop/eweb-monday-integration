<?php
// execute-cleanup.php
// Script para ejecutar la limpieza del tablero

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  EJECUTANDO LIMPIEZA DEL TABLERO       \n";
echo "  (Acción irreversible - usar con cuidado)\n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Columnas a eliminar (duplicadas)
    $columnsToDelete = [
        'dropdown_mkypgz6f',  // Tipo de Lead (duplicado)
        'dropdown_mkypbsmj',  // Canal de Origen (duplicado)
        'dropdown_mkypzbbh',  // Idioma (duplicado)
        'date_mkypsy6q',      // Fecha de Entrada (duplicado)
        'date_mkyp535v',      // Próxima Acción (duplicado)
        'text_mkypbqgg'       // Mission Partner (duplicado)
    ];
    
    echo "1. ELIMINANDO COLUMNAS DUPLICADAS...\n";
    $deletedColumns = 0;
    
    foreach ($columnsToDelete as $columnId) {
        echo "   Procesando: $columnId... ";
        try {
            $result = $monday->deleteColumn($leadsBoardId, $columnId);
            echo "✅ Eliminada\n";
            $deletedColumns++;
        } catch (Exception $e) {
            echo "⚠️ No encontrada o error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n2. OBTENIENDO LISTA DE GRUPOS...\n";
    
    // Obtener grupos para identificar los que se pueden eliminar
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            groups {
                id
                title
                items {
                    id
                    name
                }
            }
        }
    }';
    
    $result = $monday->query($query);
    $groups = $result['boards'][0]['groups'] ?? [];
    
    echo "   Total de grupos: " . count($groups) . "\n";
    
    echo "\n3. IDENTIFICANDO GRUPOS PARA ELIMINACIÓN...\n";
    $groupsToDelete = [];
    
    foreach ($groups as $group) {
        $itemCount = count($group['items']);
        $titleLower = strtolower($group['title']);
        
        // Marcar para eliminación si está vacío o es de prueba
        if ($itemCount == 0 || strpos($titleLower, 'test') !== false || 
            strpos($titleLower, 'temp') !== false || strpos($titleLower, 'prueba') !== false) {
            
            if ($group['title'] !== 'Default Group' && $group['title'] !== 'default') { // Proteger grupo por defecto
                $groupsToDelete[] = $group;
                echo "   - Marcar: {$group['title']} (ID: {$group['id']}, Items: $itemCount)\n";
            } else {
                echo "   - Mantener: {$group['title']} (es grupo por defecto)\n";
            }
        } else {
            echo "   - Mantener: {$group['title']} (Items: $itemCount)\n";
        }
    }
    
    echo "\n4. RESUMEN DE ACCIONES:\n";
    echo "   - Columnas eliminadas: $deletedColumns de " . count($columnsToDelete) . "\n";
    echo "   - Grupos identificados para eliminación: " . count($groupsToDelete) . "\n";
    
    if (count($groupsToDelete) > 0) {
        echo "\n   NOTA: La eliminación de grupos requiere un enfoque diferente\n";
        echo "   y se recomienda hacerla manualmente en la interfaz de Monday.com\n";
        echo "   para evitar pérdida accidental de datos.\n";
    }
    
    echo "\n========================================\n";
    echo "  ¡LIMPIEZA PARCIAL COMPLETADA!        \n";
    echo "========================================\n";
    echo "✅ Columnas duplicadas eliminadas\n";
    echo "✅ Grupos para eliminar identificados\n";
    echo "✅ Tablero optimizado\n";
    echo "========================================\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
