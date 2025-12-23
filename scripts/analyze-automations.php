<?php
// analyze-automations.php
// Análisis de automatizaciones existentes en Monday.com

require_once '../config.php';
require_once 'MondayAPI.php';

echo "========================================\n";
echo "  ANÁLISIS DE AUTOMATIZACIONES EXISTENTES\n";
echo "  Mars Challenge CRM Integration 2026    \n";
echo "========================================\n\n";

try {
    $monday = new MondayAPI(MONDAY_API_TOKEN);
    $leadsBoardId = '18392144864';
    
    echo "OBJETIVO: Analizar automatizaciones existentes para:\n";
    echo "- Entender qué automatizaciones ya están configuradas\n";
    echo "- Identificar qué funcionalidades podemos aprovechar\n";
    echo "- Planificar nuevas automatizaciones según el blueprint\n\n";
    
    // Intentar consultar automatizaciones del tablero
    // Nota: La API de Monday no proporciona directamente las automatizaciones
    // a través de consultas GraphQL estándar, así que vamos a explorar qué
    // información podemos obtener
    
    echo "1. VERIFICANDO AUTOMATIZACIONES EXISTENTES...\n";
    
    // Consultar el tablero para ver si hay información sobre automatizaciones
    $query = '
    query {
        boards(ids: '.$leadsBoardId.') {
            name
            groups {
                id
                title
            }
            columns {
                id
                title
                type
            }
        }
    }';
    
    $result = $monday->query($query);
    $board = $result['boards'][0];
    
    echo "   Tablero: {$board['name']}\n";
    echo "   Columnas encontradas: " . count($board['columns']) . "\n";
    echo "   Grupos encontrados: " . count($board['groups']) . "\n\n";
    
    // Mostrar información sobre automatizaciones posibles basadas en el blueprint
    echo "2. AUTOMATIZACIONES SEGÚN EL BLUEPRINT ORIGINAL:\n\n";
    
    echo "CUANDO ENTRA UN NUEVO LEAD:\n";
    echo "   • Asignar automáticamente responsable (según país o tipo de lead)\n";
    echo "   • Enviar email automático de bienvenida\n";
    echo "   • Crear tarea 'Contactar en 48h'\n";
    echo "   • Calcular Lead Score automáticamente\n";
    echo "   • Definir Prioridad (hot/warm/cold)\n\n";
    
    echo "SI PASAN 48H SIN CONTACTO:\n";
    echo "   • Notificación al responsable: 'Lead pendiente de primer contacto.'\n\n";
    
    echo "SI PASAN 5 DÍAS SIN ACTUALIZACIÓN:\n";
    echo "   • Mover a 'At Risk'\n";
    echo "   • Notificación al gestor comercial global\n\n";
    
    echo "SI LEAD SCORE > 20 (HOT LEAD):\n";
    echo "   • Crear alerta roja\n";
    echo "   • Notificar a Adelino / Dirección Comercial\n\n";
    
    echo "3. AUTOMATIZACIONES PARA PIPELINES:\n";
    echo "   • Al mover a 'Reunión agendada' → Crear tarea automática, sincronizar con Calendly, generar enlace Zoom\n";
    echo "   • Al mover a 'Propuesta enviada' → Crear tarea de follow-up en 3 días\n";
    echo "   • Al mover a 'Cerrado – Ganado' → Mover a MC – Clientes Activos 2026, enviar email, notificar equipo\n";
    echo "   • Al mover a 'Cerrado – Perdido' → Solicitar motivo obligatoriamente, registrar en dashboard\n";
    echo "   • Dormant → Si pasan 90 días sin acción → mover automáticamente\n\n";
    
    echo "4. POSIBLES INTEGRACIONES IDENTIFICADAS:\n";
    echo "   • Zapier/Make para formularios del website\n";
    echo "   • Pixel Meta + Google para tracking de campañas\n";
    echo "   • Lead Ads → Monday\n";
    echo "   • ManyChat para mensajes directos de redes\n";
    echo "   • WhatsApp API con bandeja conectada\n";
    echo "   • Redirección de emails oficiales a Monday\n\n";
    
    echo "5. ESTADO ACTUAL:\n";
    echo "   • El sistema puede mover leads entre grupos según Lead Score\n";
    echo "   • Se puede detectar idioma y clasificar leads\n";
    echo "   • Se puede calcular Lead Score automáticamente\n";
    echo "   • FALTA: Implementar automatizaciones nativas de Monday.com\n\n";
    
    echo "6. PRÓXIMOS PASOS:\n";
    echo "   • Configurar automatizaciones en la interfaz de Monday.com (Fase 7)\n";
    echo "   • Crear las automatizaciones según el blueprint\n";
    echo "   • Probar y validar las automatizaciones\n\n";
    
    echo "========================================\n";
    echo "  NOTA IMPORTANTE:                     \n";
    echo "========================================\n";
    echo "La API de Monday no permite consultar   \n";
    echo "automatizaciones existentes directamente\n";
    echo "debe hacerse a través de la interfaz    \n";
    echo "web de Monday.com en:                   \n";
    echo "  Board Settings > Automations          \n";
    echo "========================================\n\n";
    
    return true;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    return false;
}

?>
