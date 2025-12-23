<?php
// workspace-full-audit.php
// Auditoría completa del workspace

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "========================================\n";
echo "  AUDITORÍA COMPLETA DEL WORKSPACE       \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "  ANÁLISIS EXHAUSTIVO                   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    
    echo "1. CONSULTANDO TODOS LOS TABLEROS...\n\n";
    
    // Consultar todos los tableros con toda la información posible
    $query = '
    query {
        boards(limit: 100) {
            id
            name
            board_kind
            description
            state
            workspace_id
            workspace {
                id
                name
            }
            permissions
            created_at
            updated_at
            owners {
                id
                name
                email
            }
            subscribers {
                id
                name
                email
            }
            tags {
                id
                name
            }
            views {
                id
                name
                type
                settings
            }
            groups {
                id
                title
                archived
                deleted
                color
                position
            }
            columns {
                id
                title
                type
                settings_str
                description
            }
        }

        users {
            id
            name
            email
            enabled
            join_date
        }

        tags {
            id
            name
            color
        }
    }';
    
    $result = $monday->query($query);
    
    $boards = $result['boards'] ?? [];
    $users = $result['users'] ?? [];
    $tags = $result['tags'] ?? [];
    
    echo "TOTAL DE TABLEROS ENCONTRADOS: " . count($boards) . "\n";
    echo "TOTAL DE USUARIOS: " . count($users) . "\n";
    echo "TOTAL DE TAGS: " . count($tags) . "\n\n";
    
    echo "2. DETALLES DE CADA TABLERO:\n\n";
    
    foreach ($boards as $index => $board) {
        echo "=== TABLERO " . ($index + 1) . " ===\n";
        echo "ID: {$board['id']}\n";
        echo "Nombre: {$board['name']}\n";
        echo "Tipo: {$board['board_kind']}\n";
        echo "Estado: {$board['state']}\n";
        echo "Workspace ID: {$board['workspace_id']}\n";
        echo "Workspace: {$board['workspace']['name']}\n";
        echo "Folder ID: {$board['folder_id']}\n";
        echo "Permisos: {$board['permissions']}\n";
        echo "Creado: {$board['created_at']}\n";
        echo "Actualizado: {$board['updated_at']}\n";
        
        if (!empty($board['description'])) {
            echo "Descripción: {$board['description']}\n";
        }
        
        echo "Propietarios: " . count($board['owners']) . "\n";
        foreach ($board['owners'] as $owner) {
            echo "  - {$owner['name']} ({$owner['email']})\n";
        }
        
        echo "Suscriptores: " . count($board['subscribers']) . "\n";
        foreach ($board['subscribers'] as $subscriber) {
            echo "  - {$subscriber['name']} ({$subscriber['email']})\n";
        }
        
        echo "Tags: " . count($board['tags']) . "\n";
        foreach ($board['tags'] as $tag) {
            echo "  - {$tag['name']}\n";
        }
        
        echo "Vistas: " . count($board['views']) . "\n";
        foreach ($board['views'] as $view) {
            echo "  - {$view['name']} ({$view['type']})\n";
        }
        
        echo "Grupos: " . count($board['groups']) . "\n";
        foreach ($board['groups'] as $group) {
            echo "  - ID: {$group['id']}, Título: {$group['title']}, Archivado: " . ($group['archived'] ? 'Sí' : 'No') . ", Posición: {$group['position']}\n";
        }
        
        echo "Columnas: " . count($board['columns']) . "\n";
        foreach ($board['columns'] as $column) {
            echo "  - ID: {$column['id']}, Título: {$column['title']}, Tipo: {$column['type']}\n";
            if (!empty($column['description'])) {
                echo "    Descripción: {$column['description']}\n";
            }
            if (!empty($column['settings_str'])) {
                echo "    Configuración: {$column['settings_str']}\n";
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    echo "3. INFORMACIÓN DE USUARIOS:\n\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}\n";
        echo "Nombre: {$user['name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Activo: " . ($user['enabled'] ? 'Sí' : 'No') . "\n";
        if (!empty($user['birthday'])) {
            echo "Cumpleaños: {$user['birthday']}\n";
        }
        if (!empty($user['country_code'])) {
            echo "Código país: {$user['country_code']}\n";
        }
        if (!empty($user['join_date'])) {
            echo "Fecha de unión: {$user['join_date']}\n";
        }
        echo "---\n";
    }
    
    echo "\n4. INFORMACIÓN DE TAGS:\n\n";
    foreach ($tags as $tag) {
        echo "ID: {$tag['id']}\n";
        echo "Nombre: {$tag['name']}\n";
        echo "Color: {$tag['color']}\n";
        echo "---\n";
    }
    
    echo "\n5. RESUMEN POR WORKSPACE:\n\n";
    
    // Agrupar tableros por workspace
    $workspaces = [];
    foreach ($boards as $board) {
        $workspaceId = $board['workspace']['id'];
        $workspaceName = $board['workspace']['name'];
        
        if (!isset($workspaces[$workspaceId])) {
            $workspaces[$workspaceId] = [
                'name' => $workspaceName,
                'boards' => []
            ];
        }
        
        $workspaces[$workspaceId]['boards'][] = [
            'id' => $board['id'],
            'name' => $board['name'],
            'type' => $board['board_kind']
        ];
    }
    
    foreach ($workspaces as $workspaceId => $workspace) {
        echo "WORKSPACE: {$workspace['name']} (ID: $workspaceId)\n";
        echo "Tableros: " . count($workspace['boards']) . "\n";
        foreach ($workspace['boards'] as $board) {
            echo "  - {$board['name']} ({$board['type']}) - ID: {$board['id']}\n";
        }
        echo "\n";
    }
    
    echo "========================================\n";
    echo "  FIN DE LA AUDITORÍA COMPLETA         \n";
    echo "========================================\n";
    echo "Esta auditoría completa proporciona:  \n";
    echo "- Todos los tableros y su estructura   \n";
    echo "- Información detallada de columnas   \n";
    echo "- Grupos y vistas existentes          \n";
    echo "- Usuarios y permisos                 \n";
    echo "- Tags y categorías                   \n";
    echo "- Estructura por workspaces           \n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>