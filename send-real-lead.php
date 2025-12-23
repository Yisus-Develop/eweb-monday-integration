<?php
// send-real-lead.php
// Script para enviar un lead real con datos válidos para la configuración actual del tablero

require_once '../config.php';
require_once 'MondayAPI.php';

function sendRealLead() {
    echo "========================================\n";
    echo "  ENVÍO DE LEAD REAL A MONDAY.COM        \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "🎯 Enviando lead al tablero: $leadsBoardId\n\n";
        
        // Datos reales para crear el lead
        $leadName = 'Test Lead - ' . date('Y-m-d H:i:s');
        $leadEmail = 'test-' . time() . '@example.com';
        
        // Valores que coinciden con las etiquetas existentes en el tablero
        $columnValues = [
            'name' => $leadName, // Nombre del ítem
            'lead_email' => ['email' => $leadEmail, 'text' => $leadEmail],
            'lead_company' => 'Test Company Corp',
            'text' => 'Prueba de contacto automatizado',
            'lead_phone' => ['phone' => '123456789', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'], // Etiqueta válida: 'Lead nuevo', '', 'No calificado', 'Contactado', 'Intento de contacto', 'Calificado'
            
            // Columnas de negocio - usando etiquetas reales del tablero
            'numeric_mkyn2py0' => 15, // Lead Score
            'color_mkypv3rg' => ['label' => 'Listo'], // Clasificación - etiquetas reales: 'En curso', 'Listo', 'Detenido'
            'color_mkyng649' => ['label' => 'En curso'], // Rol Detectado - etiquetas reales: 'En curso', 'Listo', 'Detenido'
            'text_mkyn95hk' => 'España', // País
            
            // Otras columnas (solo las esenciales)
            'text_mkypn0m' => 'Test MP',
            'date_mkyp6w4t' => ['date' => date('Y-m-d')], // Fecha de Entrada
            'long_text_mkypqppc' => 'Lead creado automáticamente para prueba de integración'
        ];
        
        echo "📤 Enviando lead con los siguientes datos:\n";
        echo "   - Nombre: $leadName\n";
        echo "   - Email: $leadEmail\n";
        echo "   - Compañía: Test Company Corp\n";
        echo "   - Teléfono: 123456789\n";
        echo "   - Estado: Lead nuevo\n";
        echo "   - Lead Score: 15\n";
        echo "   - Clasificación: Listo\n";
        echo "   - País: España\n\n";
        
        // Intentar crear el ítem
        $itemResponse = $monday->createItem($leadsBoardId, $leadName, $columnValues);
        
        if (isset($itemResponse['create_item']['id'])) {
            $itemId = $itemResponse['create_item']['id'];
            echo "✅ ¡SUCCESS! Lead enviado exitosamente a Monday.com\n";
            echo "   ✅ ID del ítem creado: $itemId\n";
            echo "   ✅ El sistema está funcionando correctamente\n";
            echo "   ✅ El lead aparecerá en el tablero de Leads\n\n";
            
            echo "🔍 PRÓXIMOS PASOS:\n";
            echo "   1. Verificar el lead en el tablero de Monday.com\n";
            echo "   2. Confirmar que todos los campos se poblaron correctamente\n";
            echo "   3. Si todo funciona, proceder con el despliegue a producción\n";
            echo "   4. Recordar configurar manualmente las etiquetas de clasificación\n\n";
            
            return $itemId;
        } else {
            echo "❌ Error al crear el ítem: " . json_encode($itemResponse) . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar el envío
$itemId = sendRealLead();

if ($itemId) {
    echo "================================================\n";
    echo "        ¡ENVÍO DE LEAD EXITOSO!                  \n";
    echo "================================================\n";
    echo "El lead ha sido creado en Monday.com con éxito. \n";
    echo "ID del ítem: $itemId                             \n";
    echo "Puedes verificarlo en el tablero de Leads.      \n";
    echo "================================================\n";
} else {
    echo "================================================\n";
    echo "        ¡ERROR EN EL ENVÍO!                      \n";
    echo "================================================\n";
    echo "No se pudo crear el lead en Monday.com.         \n";
    echo "Verifica el token y la conexión con la API.     \n";
    echo "================================================\n";
}
?>
