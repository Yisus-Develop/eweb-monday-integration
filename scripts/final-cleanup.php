<?php
// final-cleanup.php
// Limpieza final del tablero con conocimiento completo

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  LIMPIEZA FINAL DEL TABLERO LEADS       \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    // Obtener una vista general del tablero
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            groups {
                id
                title
                archived
                items {
                    id
                    name
                    created_at
                }
            }
        }
    }';
    
    $result = $monday->query($query);
    $board = $result['boards'][0];
    $groups = $board['groups'] ?? [];
    
    echo "ESTADO ACTUAL DEL TABLERO:\n";
    echo "  - Nombre: {$board['name']}\n";
    echo "  - Total de grupos: " . count($groups) . "\n\n";
    
    // Mostrar información de cada grupo
    echo "GRUPOS EXISTENTES:\n";
    $groupsToClean = [];
    $activeGroups = [];
    
    foreach ($groups as $group) {
        $itemCount = count($group['items']);
        echo "  - ID: {$group['id']}\n";
        echo "    Título: {$group['title']}\n";
        echo "    Estado: " . ($group['archived'] ? 'Archivado' : 'Activo') . "\n";
        echo "    Items: $itemCount\n";
        
        // Identificar grupos que podrían limpiarse
        $titleLower = strtolower($group['title']);
        if ($itemCount == 0 || strpos($titleLower, 'test') !== false || 
            strpos($titleLower, 'temp') !== false || strpos($titleLower, 'prueba') !== false) {
            $groupsToClean[] = $group;
            echo "    Acción: Marcar para limpieza\n";
        } else {
            $activeGroups[] = $group;
            echo "    Acción: Mantener\n";
        }
        echo "\n";
    }
    
    echo "RESUMEN DE LIMPIEZA:\n";
    echo "  - Grupos para limpiar: " . count($groupsToClean) . "\n";
    echo "  - Grupos activos: " . count($activeGroups) . "\n\n";
    
    // Mostrar sugerencia de reorganización
    echo "SUGERENCIA DE REORGANIZACIÓN:\n";
    echo "  1. Mantener grupos activos con leads reales\n";
    echo "  2. Eliminar grupos vacíos o de prueba\n";
    echo "  3. Considerar crear grupo 'Leads Recientes' para nuevos leads\n\n";
    
    // Informar sobre columnas duplicadas encontradas
    echo "COLUMNAS DUPLICADAS IDENTIFICADAS:\n";
    echo "  - 'Fecha de Entrada' (date_mkypsy6q y date_mkyp6w4t)\n";
    echo "  - 'Próxima Acción' (date_mkypeap2 y date_mkyp535v)\n";
    echo "  - 'Tipo de Lead' (dropdown_mkypgz6f y type_of_lead)\n";
    echo "  - 'Canal de Origen' (dropdown_mkypbsmj y source_channel)\n";
    echo "  - 'Idioma' (dropdown_mkypzbbh y language)\n";
    echo "  - 'Mission Partner' (text_mkypbqgg y text_mkypn0m)\n\n";
    
    echo "RECOMENDACIONES:\n";
    echo "  1. Eliminar las columnas originales duplicadas:\n";
    echo "     - dropdown_mkypgz6f (Tipo de Lead)\n";
    echo "     - dropdown_mkypbsmj (Canal de Origen)\n";
    echo "     - dropdown_mkypzbbh (Idioma)\n";
    echo "     - date_mkypsy6q (Fecha de Entrada)\n";
    echo "     - date_mkypeap2 (Próxima Acción)\n";
    echo "     - text_mkypbqgg (Mission Partner)\n";
    echo "  2. Mantener las nuevas columnas con IDs correctos\n\n";
    
    // Mostrar estado final ideal del tablero
    echo "ESTADO FINAL IDEAL DEL TABLERO:\n";
    echo "  ✅ Columnas funcionales:\n";
    echo "    - Clasificación (classification_status) - status con HOT/WARM/COLD\n";
    echo "    - Rol Detectado (role_detected_new) - status con roles específicos\n";
    echo "    - Tipo de Lead (type_of_lead) - dropdown con opciones\n";
    echo "    - Canal de Origen (source_channel) - dropdown con opciones\n";
    echo "    - Idioma (language) - dropdown con opciones\n";
    echo "  ✅ Estructura organizada\n";
    echo "  ✅ Sin duplicados ni grupos innecesarios\n\n";
    
    echo "========================================\n";
    echo "  ¡LIMPIEZA DOCUMENTADA!                \n";
    echo "========================================\n";
    echo "Puedes proceder con la limpieza manual en la interfaz de Monday.com\n";
    echo "basándote en esta documentación.\n";
    echo "========================================\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
