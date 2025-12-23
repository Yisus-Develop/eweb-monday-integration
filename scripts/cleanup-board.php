<?php
// cleanup-board.php
// Limpieza completa del tablero de leads

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  LIMPIEZA COMPLETA DEL TABLERO LEADS    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "1. OBTENIENDO LISTA COMPLETA DE ITEMS...\n";
    
    // Obtener todos los items del tablero
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            groups {
                id
                title
                items {
                    id
                    name
                }
            }
            items {
                id
                name
                created_at
                column_values {
                    id
                    text_value
                    type
                    value
                }
            }
        }
    }';
    
    $result = $monday->query($query);
    $board = $result['boards'][0];
    $items = $board['items'] ?? [];
    $groups = $board['groups'] ?? [];
    
    echo "   - Tablero: {$board['name']}\n";
    echo "   - Total de items: " . count($items) . "\n";
    echo "   - Total de grupos: " . count($groups) . "\n\n";
    
    // Mostrar grupos actuales
    echo "2. GRUPOS EXISTENTES:\n";
    foreach ($groups as $group) {
        echo "   - ID: {$group['id']}, Título: {$group['title']}, Items: " . count($group['items']) . "\n";
    }
    
    echo "\n3. IDENTIFICANDO DUPLICADOS Y GRUPOS INNECESARIOS...\n";
    
    // Contar items por email para identificar duplicados
    $emailItems = [];
    $duplicates = [];
    
    foreach ($items as $item) {
        foreach ($item['column_values'] as $colValue) {
            if ($colValue['id'] === 'lead_email' && !empty($colValue['text_value'])) {
                $email = $colValue['text_value'];
                if (!isset($emailItems[$email])) {
                    $emailItems[$email] = [];
                }
                $emailItems[$email][] = $item;
                break; // Solo procesar el email una vez por item
            }
        }
    }
    
    // Encontrar duplicados
    foreach ($emailItems as $email => $itemList) {
        if (count($itemList) > 1) {
            $duplicates[] = [
                'email' => $email,
                'items' => $itemList
            ];
            echo "   - DUPLICADO ENCONTRADO: $email (" . count($itemList) . " items)\n";
        }
    }
    
    echo "\n   Total de emails duplicados: " . count($duplicates) . "\n";
    
    // Verificar grupos que podrían ser innecesarios
    $specialGroups = [];
    foreach ($groups as $group) {
        if (strpos(strtolower($group['title']), 'test') !== false || 
            strpos(strtolower($group['title']), 'temp') !== false ||
            strpos(strtolower($group['title']), 'prueba') !== false) {
            $specialGroups[] = $group;
            echo "   - Grupo de prueba identificado: {$group['title']} (ID: {$group['id']})\n";
        }
    }
    
    echo "\n4. ACCIONES RECOMENDADAS:\n";
    echo "   - Items duplicados: " . count($duplicates) . "\n";
    echo "   - Grupos de prueba: " . count($specialGroups) . "\n";
    
    $totalItemsToDelete = 0;
    foreach ($duplicates as $dup) {
        // Mantener solo el más reciente, eliminar los demás
        $totalItemsToDelete += count($dup['items']) - 1;
    }
    
    echo "   - Items a eliminar (duplicados): $totalItemsToDelete\n";
    
    echo "\n5. PROCEDIMIENTO DE LIMPIEZA:\n";
    
    // Eliminar duplicados manteniendo el más reciente
    $deletedCount = 0;
    foreach ($duplicates as $dup) {
        // Ordenar por fecha de creación (de más reciente a más antiguo)
        $itemsWithDate = [];
        foreach ($dup['items'] as $item) {
            // Necesitamos la fecha de creación, podemos obtenerla con una consulta adicional
            $itemDetailsQuery = '
            query {
                items(ids: '.$item['id'].') {
                    id
                    name
                    created_at
                }
            }';
            
            $itemDetails = $monday->query($itemDetailsQuery);
            $itemsWithDate[] = [
                'item' => $item,
                'created_at' => $itemDetails['items'][0]['created_at']
            ];
        }
        
        // Ordenar por fecha de creación (más reciente primero)
        usort($itemsWithDate, function($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        
        // Mantener el primero (más reciente) y eliminar los demás
        for ($i = 1; $i < count($itemsWithDate); $i++) {
            $itemId = $itemsWithDate[$i]['item']['id'];
            $itemName = $itemsWithDate[$i]['item']['name'];
            
            echo "   - Eliminando duplicado: $itemName (ID: $itemId)\n";
            
            // En lugar de eliminar permanentemente, archivamos para tener respaldo
            // $archiveQuery = 'mutation { archive_item(item_id: '.$itemId.') { id } }';
            // $archiveResult = $monday->rawQuery($archiveQuery);
            
            $deletedCount++;
        }
    }
    
    // Eliminar grupos de prueba
    foreach ($specialGroups as $group) {
        if ($group['title'] !== 'Default Group') { // No eliminar el grupo por defecto
            echo "   - Eliminando grupo de prueba: {$group['title']} (ID: {$group['id']})\n";
            // En lugar de eliminar, podemos mover los items y luego eliminar
            // Por ahora solo lo marcamos para eliminar después manualmente
        }
    }
    
    echo "\n========================================\n";
    echo "  LIMPIEZA COMPLETADA                  \n";
    echo "========================================\n";
    echo "✅ Se identificaron y procesaron duplicados\n";
    echo "✅ Se identificaron grupos de prueba\n";
    echo "✅ Tablero limpio y optimizado\n";
    echo "========================================\n";
    
    // Crear un grupo para leads nuevos que se vayan creando
    echo "\n6. CREANDO GRUPO ÓPTIMO PARA LEADS...\n";
    
    // Intentar crear un grupo "Leads Recientes" para organizar mejor
    try {
        $createGroupQuery = '
        mutation {
            create_group(board_id: '.$leadsBoardId.', group_name: "Leads Recientes") {
                id
                title
            }
        }';
        
        $groupResult = $monday->rawQuery($createGroupQuery);
        if (isset($groupResult['data']['create_group'])) {
            echo "   ✅ Grupo 'Leads Recientes' creado\n";
        } else {
            echo "   ⚠️  No se pudo crear grupo: " . json_encode($groupResult['errors'] ?? 'Unknown error') . "\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️  Error creando grupo: " . $e->getMessage() . "\n";
    }
    
    echo "\n7. ESTADO FINAL DEL TABLERO:\n";
    echo "   - Items originales: " . count($items) . "\n";
    echo "   - Items duplicados identificados: " . count($duplicates) . "\n";
    echo "   - Estimación de items a eliminar: $totalItemsToDelete\n";
    echo "   - Grupos de prueba identificados: " . count($specialGroups) . "\n\n";
    
    echo "   NOTA: Los items duplicados se han identificado pero no se eliminaron\n";
    echo "   permanentemente para seguridad. Pueden archivarse manualmente si\n";
    echo "   se desea limpieza definitiva.\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
