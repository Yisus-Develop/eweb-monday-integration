<?php
// check-workspace.php
require_once '../config.php';
require_once 'MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$boardId = (int)MONDAY_BOARD_ID; // 18392205833
$targetWorkspaceId = 13608938; // ID extracted from user's URL

echo "--- Verificando Relación Tablero - Espacio de Trabajo ---\n";

// 1. Consultar el Board para ver su Workspace ID
$queryBoard = 'query ($boardId: ID!) {
    boards (ids: [$boardId]) {
        id
        name
        workspace_id
    }
}';

try {
    $result = $monday->query($queryBoard, ['boardId' => $boardId]);
    $board = $result['boards'][0] ?? null;

    if ($board) {
        echo "Tablero Analizado: \"{$board['name']}\" (ID: {$board['id']})\n";
        $actualWorkspaceId = $board['workspace_id'];
        
        echo "Está ubicado en Workspace ID: " . ($actualWorkspaceId ?? "Principal (Main)") . "\n";
        echo "Link proporcionado termina en: $targetWorkspaceId\n\n";
        
        if ($actualWorkspaceId == $targetWorkspaceId) {
            echo "✅ SÍ. El tablero \"{$board['name']}\" vive dentro de ese Espacio de Trabajo.\n";
            echo "Ese link (Workspace) contiene a nuestro tablero.\n";
        } else {
            echo "❌ NO COINCIDE EXACTAMENTE.\n";
            echo "El tablero está en el workspace '$actualWorkspaceId', la URL apunta al workspace '$targetWorkspaceId'.\n";
            echo "Sin embargo, puede que tengas acceso a ambos.\n";
        }
    } else {
        echo "❌ No se encontró el tablero $boardId con el token actual.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
