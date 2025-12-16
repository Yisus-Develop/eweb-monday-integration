<?php
// deep-scan-workspace.php
// Objetivo: Escaneo recursivo y profundo de TODO el Workspace (Tableros -> Grupos -> Columnas)
// Genera un reporte detallado para decidir reutilización.

require_once 'config.php';
require_once 'MondayAPI.php';

$workspaceId = 13608938;
$monday = new MondayAPI(MONDAY_API_TOKEN);

echo "--- 🕵️‍♂️ Iniciando Escaneo Profundo del Workspace $workspaceId ---\n";

try {
    // 1. Obtener lista de tableros del Workspace
    echo "1. Listando tableros...\n";
    
    // Usamos el método de filtrado manual que funcionó antes
    $queryList = 'query {
        boards (limit: 50) {
            id
            name
            state
            workspace_id
        }
    }';
    
    $resultList = $monday->query($queryList);
    $targetBoards = [];
    
    foreach ($resultList['boards'] as $b) {
        if (isset($b['workspace_id']) && (int)$b['workspace_id'] === $workspaceId) {
            $targetBoards[] = $b;
        }
    }

    $count = count($targetBoards);
    echo "   ✅ Encontrados $count tableros en el objetivo.\n";

    // 2. Iterar y profundizar
    $fullAudit = [];
    
    foreach ($targetBoards as $index => $board) {
        $bid = $board['id'];
        $bname = $board['name'];
        echo "   [$index/$count] Analizando: $bname ($bid)...\n";

        // Query detallada por tablero
        // Pedimos Columnas y Grupos. (Views a veces da error de permisos, probaremos basic)
        $queryDetail = 'query ($boardId: ID!) {
            boards (ids: [$boardId]) {
                columns {
                    id
                    title
                    type
                    # settings_str # Omitido por estabilidad
                }
                groups {
                    id
                    title
                }
                views {
                    id
                    name
                    type
                }
            }
        }';

        $detail = $monday->query($queryDetail, ['boardId' => (int)$bid]);
        
        if (!empty($detail['boards'])) {
            $bData = $detail['boards'][0];
            $fullAudit[] = [
                'id' => $bid,
                'name' => $bname,
                'groups' => array_map(function($g) { return $g['title']; }, $bData['groups']),
                'columns' => array_map(function($c) { return "{$c['title']} ({$c['type']})"; }, $bData['columns']),
                'views' => isset($bData['views']) ? array_map(function($v) { return "{$v['name']} ({$v['type']})"; }, $bData['views']) : []
            ];
        }
        
        // Pequeña pausa para no saturar la API
        usleep(200000); // 0.2s
    }

    // 3. Generar Reporte JSON
    file_put_contents('workspace_full_detail.json', json_encode($fullAudit, JSON_PRETTY_PRINT));
    
    // 4. Generar Reporte Markdown (Simplificado para lectura humana)
    $mdReport = "# Auditoría Profunda de Workspace: $workspaceId\n\n";
    $mdReport .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $mdReport .= "Total Tableros: $count\n\n";
    
    foreach ($fullAudit as $item) {
        $mdReport .= "## 📌 Tablero: {$item['name']}\n";
        $mdReport .= "**ID**: `{$item['id']}`\n\n";
        
        $mdReport .= "### 📥 Grupos (" . count($item['groups']) . ")\n";
        foreach ($item['groups'] as $g) $mdReport .= "- $g\n";
        
        $mdReport .= "\n### 📊 Columnas (" . count($item['columns']) . ")\n";
        foreach ($item['columns'] as $c) $mdReport .= "- $c\n";
        
        $mdReport .= "\n### 👁️ Vistas (" . count($item['views']) . ")\n";
        if (empty($item['views'])) $mdReport .= "(Sin vistas accesibles)\n";
        foreach ($item['views'] as $v) $mdReport .= "- $v\n";
        
        $mdReport .= "\n---\n\n";
    }
    
    file_put_contents('../../WORKSPACE-FULL-AUDIT.md', $mdReport);

    echo "✅ Auditoría completada.\n";
    echo "   📄 JSON: src/wordpress/workspace_full_detail.json\n";
    echo "   📄 Reporte: PROJECTS/monday-automation/WORKSPACE-FULL-AUDIT.md\n";

} catch (Exception $e) {
    echo "❌ Error Fatal: " . $e->getMessage() . "\n";
}
