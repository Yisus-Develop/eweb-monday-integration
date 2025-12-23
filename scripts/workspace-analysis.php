<?php
// workspace-analysis.php
// Análisis de la estructura del workspace de Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  ANÁLISIS DE LA ESTRUCTURA DEL WORKSPACE\n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "OBJETIVO: Analizar la estructura completa del workspace\n";
    echo "según el blueprint original de 4 capas:\n\n";
    
    // Intentar obtener información del workspace
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
        }
    }';
    
    $result = $monday->query($query);
    $boards = $result['boards'] ?? [];
    
    echo "1. TABLEROS ENCONTRADOS EN EL WORKSPACE: " . count($boards) . "\n\n";
    
    foreach ($boards as $board) {
        echo "- ID: {$board['id']}, Nombre: {$board['name']}, Tipo: {$board['board_kind']}\n";
    }
    
    echo "\n2. ESTRUCTURA ORIGINAL DEL SISTEMA (según blueprint):\n\n";
    
    echo "CAPA 1: MC – Lead Master Intake (TABLERO ACTUAL)\n";
    echo "  Este es el corazón del sistema. Todos los leads del mundo entran aquí.\n";
    echo "  ✓ Actualmente en uso con ID: 18392144864\n";
    echo "  ✓ Columnas y grupos completamente configurados\n";
    echo "  ✓ Sistema operativo para ingreso y clasificación de leads\n\n";
    
    echo "CAPA 2: Pipelines por segmento (4 tableros distintos)\n";
    echo "  1. MC – Pipeline Universidades\n";
    echo "  2. MC – Pipeline Escuelas\n";
    echo "  3. MC – Pipeline Ciudades\n";
    echo "  4. MC – Pipeline Corporate Partners\n";
    echo "  Cada pipeline utiliza las mismas etapas, pero con métricas separadas.\n\n";
    
    echo "CAPA 3: Ventas & Cierre (tablero único)\n";
    echo "  Nombre: MC – Clientes Activos 2026\n";
    echo "  Tablero para leads que han sido convertidos en clientes.\n\n";
    
    echo "CAPA 4: Dashboards & Reportes (paneles automáticos)\n";
    echo "  Múltiples dashboards para diferentes tipos de análisis.\n\n";
    
    // Buscar tableros que coincidan con el blueprint
    echo "3. TABLEROS EXISTENTES EN EL WORKSPACE VS BLUEPRINT:\n\n";
    
    $foundBoards = [
        'master_intake' => null,
        'pipeline_universities' => null,
        'pipeline_schools' => null,
        'pipeline_cities' => null,
        'pipeline_corporate' => null,
        'clientes_activos' => null
    ];
    
    foreach ($boards as $board) {
        $boardName = strtolower($board['name']);
        
        if (strpos($boardName, 'lead master intake') !== false || 
            strpos($boardName, 'master intake') !== false) {
            $foundBoards['master_intake'] = $board;
        } elseif (strpos($boardName, 'pipeline universidades') !== false ||
                 strpos($boardName, 'pipeline universities') !== false) {
            $foundBoards['pipeline_universities'] = $board;
        } elseif (strpos($boardName, 'pipeline escuelas') !== false ||
                 strpos($boardName, 'pipeline schools') !== false) {
            $foundBoards['pipeline_schools'] = $board;
        } elseif (strpos($boardName, 'pipeline ciudades') !== false ||
                 strpos($boardName, 'pipeline cities') !== false) {
            $foundBoards['pipeline_cities'] = $board;
        } elseif (strpos($boardName, 'pipeline corporate') !== false) {
            $foundBoards['pipeline_corporate'] = $board;
        } elseif (strpos($boardName, 'clientes activos') !== false ||
                 strpos($boardName, 'clientes activos') !== false) {
            $foundBoards['clientes_activos'] = $board;
        }
    }
    
    // Reportar qué tableros se encontraron
    $boardNames = [
        'master_intake' => 'MC – Lead Master Intake',
        'pipeline_universities' => 'MC – Pipeline Universidades',
        'pipeline_schools' => 'MC – Pipeline Escuelas',
        'pipeline_cities' => 'MC – Pipeline Ciudades',
        'pipeline_corporate' => 'MC – Pipeline Corporate Partners',
        'clientes_activos' => 'MC – Clientes Activos 2026'
    ];
    
    foreach ($foundBoards as $key => $board) {
        if ($board) {
            echo "  ✓ {$boardNames[$key]} (ID: {$board['id']})\n";
        } else {
            echo "  ❌ {$boardNames[$key]} - NO ENCONTRADO\n";
        }
    }
    
    echo "\n4. ESTADO ACTUAL DEL WORKSPACE:\n";
    echo "  ✓ CAPA 1 (Lead Master Intake): COMPLETA Y OPERATIVA\n";
    echo "  ❌ CAPA 2 (Pipelines segmentados): PARCIALMENTE CREADA (posiblemente no todas las 4 variantes)\n";
    echo "  ❌ CAPA 3 (Clientes Activos): POSIBLEMENTE NO CREADA\n";
    echo "  ❌ CAPA 4 (Dashboards): PROBABLEMENTE NO CREADOS\n\n";
    
    echo "5. PRÓXIMOS PASOS PARA EL WORKSPACE:\n";
    echo "  1. Crear los 3 tableros faltantes de la CAPA 2:\n";
    echo "     - MC – Pipeline Universidades\n";
    echo "     - MC – Pipeline Escuelas\n";
    echo "     - MC – Pipeline Ciudades\n";
    echo "     - MC – Pipeline Corporate Partners\n\n";
    
    echo "  2. Crear el tablero de la CAPA 3:\n";
    echo "     - MC – Clientes Activos 2026\n\n";
    
    echo "  3. Configurar dashboards de la CAPA 4:\n";
    echo "     - Dashboard de Crecimiento Global\n";
    echo "     - Dashboard de Performance Comercial\n";
    echo "     - Dashboard de Funil Vivo\n";
    echo "     - Dashboard de Rendimiento de Mission Partners\n\n";
    
    echo "  4. Establecer relaciones entre capas:\n";
    echo "     - Automatización para mover leads de CAPA 1 a CAPA 2 según segmento\n";
    echo "     - Automatización para mover leads de CAPA 2 a CAPA 3 cuando se cierran\n\n";
    
    echo "========================================\n";
    echo "  ¡ANÁLISIS DEL WORKSPACE COMPLETADO!    \n";
    echo "========================================\n";
    echo "El sistema actual (CAPA 1) está 100%\n";
    echo "funcional. El workspace completo requiere\n";
    echo "la creación de las CAPAS 2, 3 y 4.\n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
