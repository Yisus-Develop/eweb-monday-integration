<?php
// deep-query-automations.php
// Intento avanzado de consulta de automatizaciones y metadatos

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  BÚSQUEDA AVANZADA DE AUTOMATIZACIONES  \n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "OBJETIVO: Intentar obtener información indirecta sobre automatizaciones\n";
    echo "o metadatos relacionados con automatizaciones\n\n";
    
    // Intento 1: Consulta de vistas que pueden tener automatizaciones relacionadas
    $boardId = MONDAY_BOARD_ID;
    
    $detailedQuery = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            permissions
            is_liked
            is_template
            board_layout
            layout_orientation
            views {
                id
                name
                type
                settings
                view_layout
            }
            subscribers {
                id
                name
            }
            activity_logs(limit: 10) {
                id
                event
                account {
                    id
                    name
                }
                created_at
            }
        }
    }';
    
    echo "Intentando consulta detallada con activity_logs...\n";
    
    try {
        $result = $monday->query($detailedQuery);
        $board = $result['boards'][0];
        
        echo "Tablero: {$board['name']}\n";
        echo "Permisos: {$board['permissions']}\n";
        echo "Tipo de tablero: " . ($board['is_template'] ? 'Plantilla' : 'Regular') . "\n";
        echo "Vistas encontradas: " . count($board['views']) . "\n";
        echo "Registros de actividad: " . count($board['activity_logs']) . "\n";
        
        echo "\nRegistros de actividad recientes:\n";
        foreach ($board['activity_logs'] as $log) {
            echo "  - {$log['event']} por {$log['account']['name']} en {$log['created_at']}\n";
        }
        
        echo "\nVistas disponibles:\n";
        foreach ($board['views'] as $view) {
            echo "  - {$view['name']} ({$view['type']})\n";
        }
        
    } catch (Exception $e) {
        echo "Error en la consulta detallada: " . $e->getMessage() . "\n\n";
    }
    
    // Intento 2: Consultar si hay alguna referencia a workflows o automations
    echo "\nIntentando consultar con posible alias de automatizaciones...\n";
    
    $searchQuery = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            board_relations {
                id
                source_type
            }
            column_relations {
                id
            }
        }
    }';
    
    try {
        $searchResult = $monday->query($searchQuery);
        echo "Información de relaciones encontrada:\n";
        echo "  - Relaciones de tablero: " . count($searchResult['boards'][0]['board_relations']) . "\n";
        echo "  - Relaciones de columna: " . count($searchResult['boards'][0]['column_relations']) . "\n";
    } catch (Exception $e) {
        echo "No se encontraron relaciones específicas: " . $e->getMessage() . "\n";
    }
    
    // Intento 3: Consultar posibles integraciones o servicios conectados
    echo "\nConsultando posibles integraciones...\n";
    
    $integrationQuery = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            activity_logs(limit: 20) {
                id
                event
                data
                created_at
            }
        }
    }';
    
    try {
        $integrationResult = $monday->query($integrationQuery);
        
        echo "Análisis de registros de actividad para detectar automatizaciones:\n";
        foreach ($integrationResult['boards'][0]['activity_logs'] as $log) {
            $event = $log['event'];
            $data = isset($log['data']) ? json_decode($log['data'], true) : null;
            
            echo "  - Evento: $event\n";
            echo "    Fecha: {$log['created_at']}\n";
            
            // Buscar eventos que puedan indicar automatizaciones
            if (strpos(strtolower($event), 'automation') !== false || 
                strpos(strtolower($event), 'auto') !== false ||
                strpos(strtolower($event), 'move') !== false ||
                strpos(strtolower($event), 'update') !== false) {
                echo "    >>> POSIBLE AUTOMATIZACIÓN DETECTADA <<<\n";
            }
            
            if ($data) {
                echo "    Datos: " . json_encode($data) . "\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "No se pudieron consultar los registros completos: " . $e->getMessage() . "\n";
    }
    
    // Intento 4: Consultar si hay algún tipo de workflow o proceso definido
    echo "\nIntentando otras consultas posibles...\n";
    
    $metadataQuery = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            updated_at
            created_at
            state
            board_kind
            workspace {
                id
                name
                description
            }
        }
    }';
    
    $metadataResult = $monday->query($metadataQuery);
    $boardInfo = $metadataResult['boards'][0];
    
    echo "Metadatos del tablero:\n";
    echo "  - Creado en: {$boardInfo['created_at']}\n";
    echo "  - Última actualización: {$boardInfo['updated_at']}\n";
    echo "  - Estado: {$boardInfo['state']}\n";
    echo "  - Tipo: {$boardInfo['board_kind']}\n";
    echo "  - Workspace: {$boardInfo['workspace']['name']}\n";
    
    echo "\n========================================\n";
    echo "  RESULTADO DE LA BÚSQUEDA AVANZADA      \n";
    echo "========================================\n";
    echo "1. No se encontraron campos directos de automatizaciones\n";
    echo "2. No se puede consultar directamente las automatizaciones\n";
    echo "3. Se pueden ver indicios de automatizaciones en activity_logs\n";
    echo "4. Las relaciones entre tableros podrían indicar automatizaciones\n";
    echo "5. La única forma definitiva es a través de la interfaz web\n";
    echo "========================================\n\n";
    
    echo "CONCLUSIÓN:\n";
    echo "Aunque no podemos consultar directamente las automatizaciones,\n";
    echo "podemos obtener indicios de su existencia a través de:\n";
    echo "1. Activity logs (registros de actividad)\n";
    echo "2. Eventos de movimiento o actualización de items\n";
    echo "3. Relaciones entre tableros y columnas\n";
    echo "4. Cambios que ocurren sin intervención humana directa\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    return false;
}

?>
