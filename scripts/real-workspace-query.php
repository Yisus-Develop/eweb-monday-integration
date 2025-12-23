<?php
// real-workspace-query.php
// Consulta real de la estructura del workspace usando la API

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  ESTRUCTURA REAL DEL WORKSPACE (API)    \n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "OBJETIVO: Consultar la estructura real del workspace\n";
    echo "para saber qué tableros y funcionalidades podemos aprovechar\n\n";
    
    // Consultar todos los tableros disponibles en la cuenta
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
            state
            workspace {
                id
                name
            }
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
    $boards = $result['boards'] ?? [];
    
    echo "TOTAL DE TABLEROS ENCONTRADOS: " . count($boards) . "\n\n";
    
    // Buscar tableros relacionados con CRM o ventas
    $crmBoards = [];
    $otherBoards = [];
    
    foreach ($boards as $board) {
        $boardName = strtolower($board['name']);
        
        if (strpos($boardName, 'crm') !== false || 
            strpos($boardName, 'lead') !== false ||
            strpos($boardName, 'sales') !== false ||
            strpos($boardName, 'pipeline') !== false ||
            strpos($boardName, 'contact') !== false ||
            strpos($boardName, 'account') !== false ||
            strpos($boardName, 'deal') !== false ||
            strpos($boardName, 'customer') !== false ||
            strpos($boardName, 'onboard') !== false) {
            $crmBoards[] = $board;
        } else {
            $otherBoards[] = $board;
        }
    }
    
    echo "TABLEROS RELACIONADOS CON CRM/VENTAS (" . count($crmBoards) . "):\n\n";
    
    foreach ($crmBoards as $board) {
        echo "ID: {$board['id']}\n";
        echo "Nombre: {$board['name']}\n";
        echo "Tipo: {$board['board_kind']}\n";
        echo "Estado: {$board['state']}\n";
        echo "Grupos: " . count($board['groups']) . "\n";
        echo "Columnas: " . count($board['columns']) . "\n";
        if (isset($board['workspace'])) {
            echo "Workspace: {$board['workspace']['name']} (ID: {$board['workspace']['id']})\n";
        }
        echo "---\n";
    }
    
    if (empty($crmBoards)) {
        echo "No se encontraron tableros relacionados con CRM/ventas.\n\n";
    }
    
    echo "\nOTROS TABLEROS (" . count($otherBoards) . "):\n\n";
    foreach ($otherBoards as $board) {
        echo "ID: {$board['id']}, Nombre: {$board['name']}, Tipo: {$board['board_kind']}\n";
    }
    
    echo "\nTABLERO PRINCIPAL DEL PROYECTO (ID: " . MONDAY_BOARD_ID . "):\n\n";
    
    // Consultar específicamente el tablero del proyecto
    $projectBoard = null;
    foreach ($boards as $board) {
        if ($board['id'] == MONDAY_BOARD_ID) {
            $projectBoard = $board;
            break;
        }
    }
    
    if ($projectBoard) {
        echo "Nombre: {$projectBoard['name']}\n";
        echo "Tipo: {$projectBoard['board_kind']}\n";
        echo "Estado: {$projectBoard['state']}\n";
        echo "Número de grupos: " . count($projectBoard['groups']) . "\n";
        echo "Número de columnas: " . count($projectBoard['columns']) . "\n\n";
        
        echo "GRUPOS EN EL TABLERO PRINCIPAL:\n";
        foreach ($projectBoard['groups'] as $group) {
            echo "  - {$group['id']}: {$group['title']}\n";
        }
        
        echo "\nCOLUMNAS EN EL TABLERO PRINCIPAL:\n";
        foreach ($projectBoard['columns'] as $column) {
            echo "  - {$column['id']}: {$column['title']} ({$column['type']})\n";
        }
    } else {
        echo "No se encontró el tablero principal del proyecto (ID: " . MONDAY_BOARD_ID . ")\n";
    }
    
    echo "\n========================================\n";
    echo "  ¿QUÉ PODEMOS APROVECHAR DEL WORKSPACE?\n";
    echo "========================================\n";
    echo "1. Otros tableros CRM existentes como base\n";
    echo "2. Grupos y columnas ya configuradas\n";
    echo "3. Estructuras de automatizaciones existentes\n";
    echo "4. Relaciones entre tableros (si existen)\n";
    echo "5. Plantillas de tableros que ya están en uso\n";
    echo "6. Configuraciones de vistas y dashboards\n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
