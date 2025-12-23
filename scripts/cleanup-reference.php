<?php
// DO NOT RUN - reference only
// cleanup-reference.php
// Referencia de cómo hacer limpieza (NO EJECUTAR ESTE SCRIPT)

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  REFERENCIA DE LIMPIEZA - NO EJECUTAR  \n";
echo "========================================\n\n";

echo "ESTE SCRIPT ES SOLO PARA REFERENCIA.\n";
echo "NO EJECUTARLO YA QUE REALIZA ELIMINACIONES.\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "COLUMNAS A ELIMINAR (6):\n";
    $columnsToDelete = [
        'dropdown_mkypgz6f',  // Tipo de Lead (duplicado)
        'dropdown_mkypbsmj',  // Canal de Origen (duplicado)
        'dropdown_mkypzbbh',  // Idioma (duplicado)
        'date_mkypsy6q',      // Fecha de Entrada (duplicado)
        'date_mkyp535v',      // Próxima Acción (duplicado)
        'text_mkypbqgg'       // Mission Partner (duplicado)
    ];
    
    foreach ($columnsToDelete as $columnId) {
        echo "  - $columnId\n";
    }
    
    echo "\nGRUPOS A CONSIDERAR PARA ELIMINACIÓN:\n";
    
    // Obtener grupos para revisión
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
    
    foreach ($groups as $group) {
        $itemCount = count($group['items']);
        $titleLower = strtolower($group['title']);
        
        if ($itemCount == 0 || strpos($titleLower, 'test') !== false || 
            strpos($titleLower, 'temp') !== false || strpos($titleLower, 'prueba') !== false) {
            echo "  - ID: {$group['id']}, Título: {$group['title']}, Items: $itemCount (Marcar para revisión)\n";
        } else {
            echo "  - ID: {$group['id']}, Título: {$group['title']}, Items: $itemCount (Mantener)\n";
        }
    }
    
    echo "\nINSTRUCCIONES MANUALES:\n";
    echo "1. Acceder a Monday.com > Tablero de Leads\n";
    echo "2. Para eliminar columnas:\n";
    echo "   - Clic en la columna > 'Configuración de columna' > 'Eliminar columna'\n";
    echo "3. Para eliminar grupos:\n";
    echo "   - Clic en los 3 puntos del grupo > 'Eliminar grupo'\n\n";
    
    echo "NOTA IMPORTANTE:\n";
    echo "- Las eliminaciones son IRREVERSIBLES\n";
    echo "- Verificar que no haya automatizaciones usando las columnas a eliminar\n";
    echo "- Hacer copia de seguridad antes de eliminar\n\n";
    
    echo "SOBRE GRUPOS DINÁMICOS:\n";
    echo "Monday.com permite crear automatizaciones que muevan ítems\n";
    echo "entre grupos basados en condiciones como Lead Score.\n";
    echo "Ir a: Configuración del tablero > Automatizaciones\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>
