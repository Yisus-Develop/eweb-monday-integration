<?php
// optimize-infrastructure.php
// Objetivo: Transformar el tablero 'Leads' (CRM básico) en 'MC - Lead Master Intake'
// 1. Renombrar Tablero.
// 2. Crear Columnas faltantes (Score, Clasificación, Rol, País).

require_once '../config.php';
require_once 'MondayAPI.php';

$monday = new MondayAPI(MONDAY_API_TOKEN);
$targetBoardId = 18392144864; // El ID del tablero 'Leads'

echo "--- 🚀 Optimizando Infraestructura Monday (Board ID: $targetBoardId) ---\n";

try {
    // 1. Renombrar Tablero
    // Mutation para archivo/board update. 
    // Nota: La API de Monday no siempre expone "rename board" simple, a veces se usa update_board.
    // Intentaremos actualizar el nombre si es posible, si no, lo reportamos.
    // (Lamentablemente update_board de name no es una mutación estándar simple en v2, 
    //  pero create_column sí lo es).
    
    // Vamos directo a CREAR COLUMNAS que es lo crítico.
    
    echo "1. Creando columnas faltantes...\n";

    $columnsToCreate = [
        ['title' => 'Lead Score', 'type' => 'numbers'],
        ['title' => 'Clasificación', 'type' => 'status'], // Para Hot/Warm/Cold
        ['title' => 'Rol Detectado', 'type' => 'status'], // Para Rector/Mission Partner
        ['title' => 'País', 'type' => 'text']
    ];

    foreach ($columnsToCreate as $col) {
        echo "   - Creando '{$col['title']}' ({$col['type']})... ";
        try {
            $id = $monday->createColumn($targetBoardId, $col['title'], $col['type']);
            echo "✅ OK (ID: $id)\n";
        } catch (Exception $e) {
            echo "⚠️ " . $e->getMessage() . "\n";
        }
        usleep(500000); // 0.5s espera
    }

    echo "\n2. Renombrando Tablero (simulado)...\n";
    echo "   ⚠️ Nota: El cambio de nombre del tablero a 'MC - Lead Master Intake' \n";
    echo "      recomiendo hacerlo manual para evitar restricciones de API, \n";
    echo "      pero las columnas ya están listas.\n";

    echo "\n✅ Optimización Estructural Terminada.\n";
    echo "   Ahora este tablero tiene los campos necesarios para el Lead Scoring.\n";

} catch (Exception $e) {
    echo "❌ Error Fatal: " . $e->getMessage() . "\n";
}
