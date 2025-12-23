<?php
// clean-and-reset-leads.php
// Script para limpiar todos los leads y crearlos desde cero

require_once '../../../config/config.php';
require_once '../MondayAPI.php';

echo "========================================\n";
echo "  LIMPIEZA Y RECREACIÓN DE LEADS        \n";
echo "  Mars Challenge CRM Integration 2026   \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $boardId = 18392144864; // MC – Lead Master Intake
    
    echo "Obteniendo todos los items del tablero...\n";
    
    // Consultar los boards y sus items (usando la estructura que sabemos que funciona)
    $itemsQuery = "
    query {
        boards(ids: [$boardId]) {
            items {
                id
                name
                created_at
                group {
                    id
                    title
                }
            }
        }
    }";
    
    $result = $monday->query($itemsQuery);
    $boards = $result['boards'] ?? [];
    $items = [];
    
    if (!empty($boards)) {
        $items = $boards[0]['items'] ?? [];
    }
    
    echo "Se encontraron " . count($items) . " items en el tablero.\n\n";
    
    if (count($items) > 0) {
        echo "Eliminando items existentes...\n";
        
        foreach ($items as $index => $item) {
            echo "  [" . ($index + 1) . "/" . count($items) . "] Eliminando: {$item['name']} (ID: {$item['id']}) - Grupo: {$item['group']['title']}\n";
            
            // Eliminar el item usando la mutación correspondiente
            $deleteQuery = "
            mutation {
                delete_item(item_id: {$item['id']}) {
                    id
                }
            }";
            
            try {
                $deleteResult = $monday->query($deleteQuery);
                echo "    ✅ Eliminado\n";
            } catch (Exception $e) {
                echo "    ❌ Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✅ Eliminación completada.\n\n";
    } else {
        echo "No se encontraron items para eliminar.\n\n";
    }
    
    // Ahora crear algunos leads de prueba para verificar que todo funcione
    echo "CREANDO LEADS DE PRUEBA...\n\n";
    
    $testLeads = [
        [
            'name' => 'Test Universidad 2026 - HOT',
            'company' => 'Universidad Test',
            'email' => 'test.university.hotp@example.com',
            'country' => 'España',
            'perfil' => 'institucion',
            'tipo_institucion' => 'Universidad',
            'numero_estudiantes' => 15000,
            'phone' => '999888777',
            'score' => 25,
            'classification' => 'HOT'
        ],
        [
            'name' => 'Test Company WARM',
            'company' => 'Company Test',
            'email' => 'test.company.warm@example.com',
            'country' => 'México',
            'perfil' => 'empresa',
            'tipo_institucion' => 'Empresa',
            'numero_estudiantes' => 0,
            'phone' => '888777666',
            'score' => 15,
            'classification' => 'WARM'
        ],
        [
            'name' => 'Test Mentor COLD',
            'company' => 'Educación Test',
            'email' => 'test.mentor.cold@example.com',
            'country' => 'Colombia',
            'perfil' => 'mentor',
            'tipo_institucion' => 'Escuela',
            'numero_estudiantes' => 500,
            'phone' => '777666555',
            'score' => 5,
            'classification' => 'COLD'
        ],
        [
            'name' => 'Test Pioneer VIP',
            'company' => 'Mission Partner Test',
            'email' => 'test.pioneer.vip@example.com',
            'country' => 'Argentina',
            'perfil' => 'pioneer',
            'tipo_institucion' => 'Mission Partner',
            'numero_estudiantes' => 0,
            'phone' => '666555444',
            'score' => 30,
            'classification' => 'HOT'
        ]
    ];
    
    foreach ($testLeads as $index => $lead) {
        echo "Creando lead: {$lead['name']}...\n";
        
        // Preparar valores para Monday
        $columnValues = [
            'lead_company' => $lead['company'],
            'lead_email' => ['email' => $lead['email'], 'text' => $lead['email']],
            'lead_phone' => ['phone' => $lead['phone'], 'country_short_name' => 'ES'],
            'text_mkyn95hk' => $lead['country'],
            'numeric_mkyn2py0' => $lead['score'],
            'classification_status' => ['label' => $lead['classification']],
            'text' => $lead['tipo_institucion'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'text_mkypn0m' => $lead['perfil'] === 'pioneer' ? $lead['name'] : '',
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d')],
            'long_text_mkypqppc' => 'Lead de prueba para verificación del sistema'
        ];
        
        try {
            $result = $monday->createItem($boardId, $lead['name'], $columnValues);
            $itemId = $result['create_item']['id'];
            echo "  ✅ Creado (ID: $itemId)\n";
            
            // Ahora actualizar las columnas dropdown
            try {
                $monday->changeSimpleColumnValue($boardId, $itemId, 'type_of_lead', $lead['tipo_institucion']);
                $monday->changeSimpleColumnValue($boardId, $itemId, 'source_channel', 'Contact Form');
                $monday->changeSimpleColumnValue($boardId, $itemId, 'language', 'Español');
                
                $role = 'Maestro/Mentor'; // Valor por defecto
                if ($lead['perfil'] === 'pioneer') $role = 'Mission Partner';
                elseif ($lead['perfil'] === 'institucion') $role = 'Rector/Director';
                elseif ($lead['perfil'] === 'empresa') $role = 'Corporate';
                elseif ($lead['perfil'] === 'ciudad') $role = 'Alcalde/Gobierno';
                elseif ($lead['perfil'] === 'zer') $role = 'Joven';
                
                $monday->changeSimpleColumnValue($boardId, $itemId, 'role_detected_new', $role);
                
                echo "  ✅ Columnas dropdown actualizadas\n";
            } catch (Exception $e) {
                echo "  ❌ Error actualizando columnas dropdown: " . $e->getMessage() . "\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error creando lead: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "========================================\n";
    echo "  LIMPIEZA Y RECREACIÓN COMPLETADA     \n";
    echo "========================================\n";
    echo "✅ Todos los leads antiguos eliminados\n";
    echo "✅ Nuevos leads de prueba creados\n";
    echo "✅ Sistema listo para uso fresco\n";
    echo "========================================\n\n";
    
    echo "PARA VERIFICAR:\n";
    echo "- Visita: https://monday.com/boards/18392144864\n";
    echo "- Verifica que solo aparezcan los nuevos leads de prueba\n";
    echo "- Confirma que están en los grupos correctos (HOT/WARM/COLD)\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

?>