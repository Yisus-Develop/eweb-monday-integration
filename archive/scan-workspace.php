<?php
// scan-workspace.php
// Objetivo: Listar TODOS los tableros del Workspace para ver qué infraestructura existe ya.

require_once '../config.php';
require_once 'MondayAPI.php';

$workspaceId = 13608938; // ID confirmado por el usuario

echo "--- Escaneando Workspace ID: $workspaceId ---\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);

    // Consultar tableros filtrando por Workspace ID
    // (La API de Monday a veces requiere filtrado manual si no soporta filtro directo en args)
    
    // Primero intentamos filtrar en la query (algunas versiones API lo soportan)
    $query = 'query {
        boards (limit: 50, workspace_ids: [' . $workspaceId . ']) {
            id
            name
            state
            description
            workspace_id
        }
    }';

    $result = $monday->query($query);
    
    if (empty($result['boards'])) {
        echo "⚠️ No se encontraron tableros RESPODIENDO directamente al filtro de Workspace.\n";
        echo "   Intentando listar todos y filtrar manualmente...\n";
        
        // Fallback: Listar recientes y filtrar en PHP
        $queryAll = 'query {
            boards (limit: 100) {
                id
                name
                workspace_id
            }
        }';
        $result = $monday->query($queryAll);
    }

    $foundBoards = [];
    if (!empty($result['boards'])) {
        foreach ($result['boards'] as $b) {
            // Filtrar estrictamente por si la API ignoró el filtro
            if (isset($b['workspace_id']) && (int)$b['workspace_id'] === $workspaceId) {
                $foundBoards[] = $b;
            }
        }
    }

    echo "✅ Tableros Encontrados en este Espacio: " . count($foundBoards) . "\n\n";

    if (count($foundBoards) > 0) {
        foreach ($foundBoards as $b) {
            echo "📁 [{$b['id']}] {$b['name']} ({$b['state']})\n";
        }
        
        // Guardar para análisis
        file_put_contents('workspace_structure.json', json_encode($foundBoards, JSON_PRETTY_PRINT));
        echo "\n📄 Estructura guardada en 'workspace_structure.json'.\n";
    } else {
        echo "❌ El Workspace parece estar VACÍO (o no tengo permisos para ver sus tableros).\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
