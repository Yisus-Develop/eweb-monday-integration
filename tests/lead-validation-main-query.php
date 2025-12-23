<?php
// lead-validation-main-query.php
// Consulta del tablero principal Lead Validation

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "========================================\n";
echo "  CONSULTA TABLERO PRINCIPAL:           \n";
echo "  Lead Validation (18392205833)        \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    // Consultar específicamente el tablero de Lead Validation
    $query = '
    query {
        boards(ids: [18392205833, 18392205785]) {
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
    
    foreach ($boards as $board) {
        echo "TABLERO VALIDATION ({$board['id']}):\n";
        echo "Nombre: {$board['name']}\n";
        echo "Tipo: {$board['board_kind']}\n";
        echo "Estado: {$board['state']}\n";
        echo "Descripción: " . ($board['description'] ?? 'No disponible') . "\n\n";
        
        echo "GRUPOS:\n";
        foreach ($board['groups'] as $group) {
            echo "  - ID: {$group['id']}, Título: {$group['title']}, Archivado: " . ($group['archived'] ? 'Sí' : 'No') . "\n";
        }
        
        echo "\nCOLUMNAS:\n";
        foreach ($board['columns'] as $column) {
            echo "  - ID: {$column['id']}, Título: {$column['title']}, Tipo: {$column['type']}\n";
        }
        
        echo "\n" . str_repeat("-", 40) . "\n\n";
    }
    
    echo "ANÁLISIS IMPORTANTE:\n";
    echo "Hemos encontrado 2 tableros principales de Lead Validation:\n";
    echo "1. ID: 18392205833 - Nombre: Lead Validation\n";
    echo "2. ID: 18392205785 - Nombre: Lead Validation\n\n";
    
    echo "POSIBLE USO EN EL SISTEMA MARS CHALLENGE:\n";
    echo "1. Validación inicial de calidad de leads\n";
    echo "2. Proceso de outbound para leads de baja calidad\n";
    echo "3. Intentos de contacto previos antes de intake\n";
    echo "4. Calificación de leads antes de ingresar al Master Intake\n\n";
    
    echo "POTENCIAL INTEGRACIÓN:\n";
    echo "- Los leads de baja calidad pueden ir primero a este tablero\n";
    echo "- Si se califican, pueden moverse al Lead Master Intake\n";
    echo "- Si no se pueden calificar, pueden cerrarse aquí\n";
    echo "- Puede servir como colador de leads antes del intake principal\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>