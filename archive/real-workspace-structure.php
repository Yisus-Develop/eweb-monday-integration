<?php
// real-workspace-structure.php
// Consulta real de la estructura del workspace

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  ESTRUCTURA REAL DEL WORKSPACE\n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "OBJETIVO: Consultar los tableros reales existentes en la cuenta\n\n";
    
    // Consultar todos los tableros disponibles
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
            state
        }
    }';
    
    $result = $monday->query($query);
    $boards = $result['boards'] ?? [];
    
    echo "TABLEROS ENCONTRADOS EN LA CUENTA: " . count($boards) . "\n\n";
    
    foreach ($boards as $board) {
        echo "ID: {$board['id']}\n";
        echo "Nombre: {$board['name']}\n";
        echo "Tipo: {$board['board_kind']}\n";
        echo "Estado: {$board['state']}\n";
        if (!empty($board['description'])) {
            echo "Descripción: {$board['description']}\n";
        }
        echo "---\n";
    }
    
    echo "\nTABLEROS RELACIONADOS CON EL PROYECTO MARS CHALLENGE:\n\n";
    
    // Buscar específicamente tableros relacionados con el proyecto
    $marsChallengeBoards = [];
    foreach ($boards as $board) {
        $boardName = strtolower($board['name']);
        if (strpos($boardName, 'mars') !== false || 
            strpos($boardName, 'challenge') !== false ||
            strpos($boardName, 'lead') !== false ||
            strpos($boardName, 'bdm') !== false) {
            $marsChallengeBoards[] = $board;
        }
    }
    
    if (count($marsChallengeBoards) > 0) {
        foreach ($marsChallengeBoards as $board) {
            echo "✓ ID: {$board['id']}, Nombre: {$board['name']}\n";
        }
    } else {
        echo "No se encontraron tableros relacionados con Mars Challenge.\n";
    }
    
    echo "\nBUSCANDO EL TABLERO PRINCIPAL...\n\n";
    
    // Buscar específicamente el tablero del proyecto
    $mainBoard = null;
    foreach ($boards as $board) {
        if ($board['id'] == '18392144864' || 
            strpos(strtolower($board['name']), 'leads') !== false ||
            strpos(strtolower($board['name']), 'master intake') !== false) {
            $mainBoard = $board;
            break;
        }
    }
    
    if ($mainBoard) {
        echo "TABLERO PRINCIPAL ENCONTRADO:\n";
        echo "ID: {$mainBoard['id']}\n";
        echo "Nombre: {$mainBoard['name']}\n";
        echo "Tipo: {$mainBoard['board_kind']}\n\n";
    } else {
        echo "No se encontró el tablero principal del proyecto.\n\n";
    }
    
    echo "========================================\n";
    echo "  ¡CONSULTA REAL COMPLETADA!            \n";
    echo "========================================\n";
    echo "La estructura real del workspace se ha\n";
    echo "consultado y mostrado arriba. A diferencia\n";
    echo "de las suposiciones basadas en documentos,\n";
    echo "esta información es la que realmente\n";
    echo "existe en la cuenta de Monday.com.\n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
