<?php
// verify-connection.php (Enhanced)
require_once 'config.php';
require_once 'MondayAPI.php';

echo "--- Diagnóstico Avanzado de Monday API ---\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);

    // 1. Verificar Identidad
    echo "1. Consultando 'Me' (Identidad de Token)...\n";
    $queryMe = '{ me { id name email account { name slug } } }';
    $me = $monday->query($queryMe);
    
    if (isset($me['me'])) {
        $user = $me['me'];
        echo "   ✅ Autenticado como: {$user['name']} (ID: {$user['id']})\n";
        echo "   📧 Email: {$user['email']}\n";
        echo "   🏢 Cuenta: {$user['account']['name']} (Slug: {$user['account']['slug']})\n";
    } else {
        die("❌ Falló la autenticación. El token puede ser inválido.\n");
    }

    // 2. Listar Tableros Visibles
    echo "\n2. Buscando tablero objetivo ID: " . MONDAY_BOARD_ID . "\n";
    $queryBoard = 'query ($boardId: ID!) {
        boards (ids: [$boardId]) {
            id name state permissions
        }
    }';
    
    $boardResult = $monday->query($queryBoard, ['boardId' => (int)MONDAY_BOARD_ID]);
    
    if (!empty($boardResult['boards'])) {
        $b = $boardResult['boards'][0];
        echo "   ✅ Tablero ENCONTRADO: \"{$b['name']}\"\n";
        echo "   ℹ️ Estado: {$b['state']} | Permisos: {$b['permissions']}\n";
        
        // Listar Columnas (Agregado nuevamente)
        echo "\n   --- Estructura de Columnas (IDs REALES) ---\n";
        // Necesitamos pedir las columnas en la query si no estaban
        if (!isset($b['columns'])) {
             $queryCols = 'query ($boardId: ID!) { boards (ids: [$boardId]) { columns { id title type } } }';
             $colsResult = $monday->query($queryCols, ['boardId' => (int)MONDAY_BOARD_ID]);
             $columns = $colsResult['boards'][0]['columns'] ?? [];
        } else {
             $columns = $b['columns'];
        }

        foreach ($columns as $col) {
            echo "   - [{$col['title']}] => ID: {$col['id']} (Tipo: {$col['type']})\n";
        }
        
        // Guardar columns para lectura de IA
        file_put_contents('columns_dump.json', json_encode($columns, JSON_PRETTY_PRINT));
        echo "\n✅ Estructura guardada en columns_dump.json\n";

    } else {
        echo "   ❌ Tablero NO encontrado con este Token.\n";
        echo "   ------------------------------------------------\n";
        echo "   Listando los primeros 10 tableros que SI puede ver este token:\n";
        
        $queryList = '{ boards (limit: 10) { id name } }';
        $list = $monday->query($queryList);
        
        if (!empty($list['boards'])) {
            foreach ($list['boards'] as $bd) {
                echo "   - ID: {$bd['id']} | Nombre: {$bd['name']}\n";
            }
        } else {
            echo "   (El token no puede ver ningún tablero)\n";
        }
    }

} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
}
