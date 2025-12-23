<?php
// lead-validation-template-query.php
// Consulta específica del tablero Lead Validation & Outreach Template

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "========================================\n";
echo "  CONSULTA TABLERO: Lead Validation     \n";
echo "  & Outreach Template                  \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Consultar todos los tableros para encontrar el específico
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
            state
            groups {
                id
                title
                archived
            }
            columns {
                id
                title
                type
                settings_str
            }
        }
    }';
    
    $result = $monday->query($query);
    $boards = $result['boards'] ?? [];
    
    echo "TOTAL DE TABLEROS ENCONTRADOS: " . count($boards) . "\n\n";
    
    // Buscar el tablero específico
    $targetBoard = null;
    foreach ($boards as $board) {
        $boardName = strtolower($board['name']);
        if (strpos($boardName, 'lead validation') !== false && 
            strpos($boardName, 'outreach') !== false) {
            $targetBoard = $board;
            break;
        }
        // También buscar tableros con nombre similar
        if (strpos($boardName, 'lead validation') !== false || 
            strpos($boardName, 'validation') !== false) {
            $targetBoard = $board;
            break;
        }
    }
    
    if ($targetBoard) {
        echo "TABLERO ENCONTRADO:\n";
        echo "ID: {$targetBoard['id']}\n";
        echo "Nombre: {$targetBoard['name']}\n";
        echo "Tipo: {$targetBoard['board_kind']}\n";
        echo "Estado: {$targetBoard['state']}\n";
        echo "Descripción: " . ($targetBoard['description'] ?? 'No disponible') . "\n\n";
        
        echo "GRUPOS EN EL TABLERO:\n";
        foreach ($targetBoard['groups'] as $group) {
            echo "  - ID: {$group['id']}, Título: {$group['title']}, Archivado: " . ($group['archived'] ? 'Sí' : 'No') . "\n";
        }
        
        echo "\nCOLUMNAS EN EL TABLERO:\n";
        foreach ($targetBoard['columns'] as $column) {
            echo "  - ID: {$column['id']}, Título: {$column['title']}, Tipo: {$column['type']}\n";
        }
        
        echo "\n========================================\n";
        echo "  ANÁLISIS DEL TABLERO                   \n";
        echo "========================================\n";
        
        // Verificar si este es uno de los tableros de validación que vimos antes
        $validationBoards = [];
        foreach ($boards as $board) {
            if (strpos(strtolower($board['name']), 'lead validation') !== false) {
                $validationBoards[] = $board;
            }
        }
        
        if (count($validationBoards) > 1) {
            echo "Se encontraron " . count($validationBoards) . " tableros de validación:\n";
            foreach ($validationBoards as $board) {
                echo "- ID: {$board['id']}, Nombre: {$board['name']}\n";
            }
        }
        
        echo "\nPOSIBLE FUNCIÓN EN EL SISTEMA:\n";
        echo "- Tablero de validación de leads\n";
        echo "- Proceso de calificación y outbound\n";
        echo "- Puede complementar el Lead Intake\n";
        echo "- Potencialmente usado para leads de baja calidad\n";
        echo "- Seguimiento de intentos de contacto\n\n";
        
    } else {
        echo "No se encontró un tablero con nombre similar a 'Lead Validation & Outreach Template'.\n\n";
        
        // Listar tableros que podrían ser similares
        echo "POSIBLES TABLEROS RELACIONADOS:\n";
        $relatedBoards = [];
        foreach ($boards as $board) {
            $boardName = strtolower($board['name']);
            if (strpos($boardName, 'validation') !== false || 
                strpos($boardName, 'outreach') !== false ||
                strpos($boardName, 'qualification') !== false) {
                $relatedBoards[] = $board;
            }
        }
        
        foreach ($relatedBoards as $board) {
            echo "- ID: {$board['id']}, Nombre: {$board['name']}\n";
        }
        
        if (empty($relatedBoards)) {
            echo "No se encontraron tableros relacionados.\n";
        }
        
        echo "\nBUSCANDO TABLEROS SIMILARES AL NOMBRE...\n";
        foreach ($boards as $board) {
            // Buscar por IDs que vimos antes que eran de validación
            if ($board['id'] == '18392205833' || $board['id'] == '18392205785') {
                echo "ENCONTRADO: {$board['name']} (ID: {$board['id']})\n";
                echo "  Grupos: " . count($board['groups']) . "\n";
                echo "  Columnas: " . count($board['columns']) . "\n";
            }
        }
    }
    
    echo "\n========================================\n";
    echo "  CONCLUSIÓN                            \n";
    echo "========================================\n";
    echo "Este tablero probablemente sea parte     \n";
    echo "del flujo de validación de leads         \n";
    echo "antes de ingresar al Lead Master Intake. \n";
    echo "Puede usarse para:                       \n";
    echo "- Validación inicial de calidad          \n";
    echo "- Intentos de contacto previos           \n";
    echo "- Calificación de leads antes de intake  \n";
    echo "- Proceso de outbound                    \n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>