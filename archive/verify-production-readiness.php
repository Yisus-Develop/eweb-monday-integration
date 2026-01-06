<?php
// verify-production-readiness.php
// Script de diagnóstico para verificar el estado real de Monday.com

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/MondayAPI.php';
require_once __DIR__ . '/NewColumnIds.php';

echo "========================================\n";
echo "  DIAGNÓSTICO DE PREPARACIÓN PARA PRODUCCIÓN\n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = MONDAY_BOARD_ID;
    
    echo "1. VERIFICANDO CONEXIÓN Y TABLERO (ID: $boardId)...\n";
    $query = 'query { boards(ids: '.$boardId.') { name columns { id title type settings_str } items_page(limit: 1) { items { id } } } }';
    $result = $monday->query($query);
    
    if (empty($result['boards'])) {
        throw new Exception("No se encontró el tablero con ID $boardId.");
    }
    
    $board = $result['boards'][0];
    echo "   ✅ Tablero encontrado: " . $board['name'] . "\n\n";
    
    echo "2. VERIFICANDO COLUMNAS CRÍTICAS...\n";
    $columns = $board['columns'];
    $columnMap = [];
    foreach ($columns as $col) {
        $columnMap[$col['id']] = $col;
    }
    
    $criticalColumns = [
        'classification_status' => 'Clasificación',
        'role_detected' => 'Rol Detectado',
        'type_of_lead' => 'Tipo de Lead',
        'source_channel' => 'Canal de Origen',
        'language' => 'Idioma'
    ];
    
    $allFound = true;
    foreach ($criticalColumns as $id => $title) {
        if (isset($columnMap[$id])) {
            echo "   ✅ Columna '$title' (ID: $id) encontrada.\n";
            
            // Verificar etiquetas si es Status
            if ($id === 'classification_status') {
                $settings = json_decode($columnMap[$id]['settings_str'], true);
                $labels = $settings['labels'] ?? [];
                echo "      Etiquetas encontradas: " . implode(', ', array_values($labels)) . "\n";
                if (in_array('HOT', $labels) && in_array('WARM', $labels) && in_array('COLD', $labels)) {
                    echo "      ✅ Etiquetas HOT/WARM/COLD correctas.\n";
                } else {
                    echo "      ⚠️ Etiquetas HOT/WARM/COLD NO encontradas.\n";
                    $allFound = false;
                }
            }
        } else {
            echo "   ❌ Columna '$title' (ID: $id) NO ENCONTRADA.\n";
            $allFound = false;
        }
    }
    
    echo "\n3. VERIFICANDO ITEMS PENDIENTES DE LIMPIEZA...\n";
    // Contar items (aproximado por la consulta anterior)
    $hasItems = !empty($board['items_page']['items']);
    if ($hasItems) {
        echo "   ⚠️ El tablero TIENE items existentes. Se requiere limpieza antes de producción.\n";
    } else {
        echo "   ✅ El tablero está VACÍO. Listo para empezar.\n";
    }
    
    echo "\n========================================\n";
    if ($allFound) {
        echo "   ESTADO: ¡LISTO PARA PRUEBAS FINALES!\n";
    } else {
        echo "   ESTADO: SE NECESITAN AJUSTES EN COLUMNAS.\n";
    }
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
