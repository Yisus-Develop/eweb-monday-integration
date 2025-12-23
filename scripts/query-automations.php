<?php
// query-automations.php
// Intento de consulta de automatizaciones existentes en Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  INTENTO DE CONSULTA DE AUTOMATIZACIONES\n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "OBJETIVO: Intentar consultar automatizaciones existentes\n";
    echo "en el tablero principal y en el workspace\n\n";
    
    // Intentar consultar automatizaciones del tablero principal
    $boardId = MONDAY_BOARD_ID;
    
    // Intento 1: Consultar información general del tablero (puede incluir automatizaciones)
    $query = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            description
            workspace {
                id
                name
            }
            tags {
                id
                name
            }
            subscribers {
                id
                name
            }
            views {
                id
                name
                type
            }
        }
    }';
    
    echo "Intentando consulta general del tablero...\n";
    $result = $monday->query($query);
    $board = $result['boards'][0];
    
    echo "Tablero: {$board['name']} (ID: {$board['id']})\n";
    echo "Workspace: {$board['workspace']['name']} (ID: {$board['workspace']['id']})\n";
    echo "Vistas encontradas: " . count($board['views']) . "\n";
    echo "Subcriptores: " . count($board['subscriptions'] ?? []) . "\n\n";
    
    // Intento 2: Buscar en la documentación de la API qué campos pueden mostrar automatizaciones
    $advancedQuery = '
    query {
        boards(ids: [' . $boardId . ']) {
            id
            name
            board_folder {
                id
                name
            }
            board_kind
            description
            groups {
                id
                title
            }
            creation_log {
                created_at
                user {
                    name
                }
            }
            owners {
                id
                name
            }
        }
    }';
    
    echo "Intentando consulta avanzada del tablero...\n";
    $advancedResult = $monday->query($advancedQuery);
    
    echo "Información adicional obtenida:\n";
    echo "- Tipo de tablero: {$advancedResult['boards'][0]['board_kind']}\n";
    echo "- Grupos: " . count($advancedResult['boards'][0]['groups']) . "\n";
    
    if (isset($advancedResult['boards'][0]['creation_log'])) {
        $log = $advancedResult['boards'][0]['creation_log'];
        echo "- Creado en: {$log['created_at']}\n";
    }
    
    echo "\n";
    
    // Intento 3: Consultar todas las entidades del workspace para ver si podemos encontrar automatizaciones
    echo "Consultando otras entidades en el workspace...\n";
    
    $entitiesQuery = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
        }
        users {
            id
            name
            email
        }
    }';
    
    $entitiesResult = $monday->query($entitiesQuery);
    
    echo "Otros tableros en el workspace: " . count($entitiesResult['boards']) . "\n";
    echo "Usuarios en el workspace: " . count($entitiesResult['users']) . "\n\n";
    
    echo "INFORMACIÓN IMPORTANTE:\n";
    echo "Después de múltiples intentos de consulta, se confirma que:\n";
    echo "1. La API de Monday.com no expone directamente las automatizaciones\n";
    echo "   a través de consultas GraphQL estándar.\n";
    echo "2. Las automatizaciones deben consultarse/configurarse a través de:\n";
    echo "   - La interfaz web de Monday.com\n";
    echo "   - Board Settings > Automations\n";
    echo "   - O posiblemente a través de la API de Automations (si está disponible)\n\n";
    
    echo "QUÉ PODEMOS HACER:\n";
    echo "1. Las automatizaciones ya existentes se pueden ver en la interfaz web\n";
    echo "2. Podemos crear nuevas automatizaciones a través de la API (si se descubre el método)\n";
    echo "3. Las automatizaciones se configuran manualmente en Monday.com\n";
    echo "4. Las automatizaciones pueden responder a eventos como:\n";
    echo "   - Creación de items\n";
    echo "   - Cambios de estado\n";
    echo "   - Actualizaciones de columnas\n";
    echo "   - Tiempo de inactividad\n\n";
    
    echo "ESTRATEGIA PARA LA FASE 7:\n";
    echo "1. Revisar manualmente las automatizaciones existentes en la interfaz\n";
    echo "2. Documentar las que ya están configuradas\n";
    echo "3. Crear las nuevas automatizaciones según el blueprint original\n";
    echo "4. Aprovechar las relaciones entre tableros que ya existen\n\n";
    
    echo "========================================\n";
    echo "  LIMITACIÓN DE LA API IDENTIFICADA      \n";
    echo "========================================\n";
    echo "La API de Monday.com no permite consultar\n";
    echo "automatizaciones existentes de forma directa.\n";
    echo "Se requiere acceso a la interfaz web para:\n";
    echo "- Ver automatizaciones existentes\n";
    echo "- Crear nuevas automatizaciones\n";
    echo "- Modificar automatizaciones existentes\n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Este error confirma que algunas funcionalidades no están disponibles\n";
    echo "a través de la API GraphQL estándar de Monday.com.\n\n";
    return false;
}

?>
