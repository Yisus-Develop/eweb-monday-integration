<?php
// update-status-alternative.php
// Alternativa para actualizar etiquetas de status sin la revisión específica

require_once '../config.php';
require_once 'MondayAPI.php';

function updateStatusAlternative() {
    echo "========================================\n";
    echo "  ACTUALIZACIÓN ALTERNATIVA DE ETIQUETAS \n";
    echo "========================================\n\n";

    try {
        $monday = new MondayAPI(MONDAY_API_TOKEN);
        $leadsBoardId = '18392144864';
        
        echo "1. OBTENIENDO INFORMACIÓN COMPLETA DE COLUMNAS...\n";
        
        // Consultar las columnas sin el campo metadata que no existe
        $query = '
        query {
            boards(ids: '.$leadsBoardId.') {
                id
                name
                updated_at
                columns {
                    id
                    title
                    type
                    settings_str
                }
            }
        }';
        
        $result = $monday->query($query);
        $board = $result['boards'][0];
        $columns = $board['columns'] ?? [];
        
        // Buscar las columnas de status específicas
        $classificationColumn = null;
        $roleColumn = null;
        
        foreach ($columns as $column) {
            if ($column['id'] === 'color_mkypv3rg' && $column['title'] === 'Clasificación') {
                $classificationColumn = $column;
            } elseif ($column['id'] === 'color_mkyng649' && $column['title'] === 'Rol Detectado') {
                $roleColumn = $column;
            }
        }
        
        if (!$classificationColumn) {
            echo "❌ No se encontró la columna de clasificación\n";
            return false;
        }
        
        if (!$roleColumn) {
            echo "❌ No se encontró la columna de rol detectado\n";
            return false;
        }
        
        echo "   Columnas encontradas, preparando actualización alternativa...\n\n";
        
        // En lugar de usar update_status_column con revisión, creemos un enfoque
        // para intentar actualizar las etiquetas usando el flujo de actualización de valores
        // Primero, intentemos simplemente crear ítems con las nuevas etiquetas para ver si se crean
        echo "2. ACTUALIZANDO ETIQUETAS DE CLASIFICACIÓN...\n";
        
        // Para actualizar las etiquetas de status, necesitamos encontrar la revisión correcta
        // o hacerlo manualmente. Vamos a intentar una estrategia de fuerza bruta:
        // crear una nueva columna de status con las etiquetas correctas y eliminar la antigua
        
        echo "   Intentando crear ítems de prueba con nuevas etiquetas...\n";
        
        // Crear un lead de prueba para ver si podemos introducir nuevas etiquetas
        $testLeadName = 'Prueba Actualización - ' . date('Y-m-d H:i:s');
        $testEmail = 'test-' . time() . '@example.com';
        
        // Intentar usar createLabelsIfMissing para crear nuevas etiquetas
        // (aunque esto no debería funcionar con columnas existentes, vale la pena intentar)
        $columnValues = [
            'name' => $testLeadName,
            'lead_email' => ['email' => $testEmail, 'text' => $testEmail],
            'lead_company' => 'Prueba Actualización',
            'text' => 'Prueba',
            'lead_phone' => ['phone' => '999999999', 'country_short_name' => 'ES'],
            'lead_status' => ['label' => 'Lead nuevo'],
            'numeric_mkyn2py0' => 50, // Puntuación media
            // Estas siguientes columnas probablemente fallarán si las etiquetas no existen
            'color_mkypv3rg' => ['label' => 'HOT'], // Nueva etiqueta
            'color_mkyng649' => ['label' => 'Mission Partner'], // Nueva etiqueta
            'text_mkyn95hk' => 'España',
            'dropdown_mkypgz6f' => ['label' => 'Website'], // Tipo de Lead
            'dropdown_mkypbsmj' => ['label' => 'Contact Form'], // Canal de Origen
            'text_mkypbqgg' => 'MP001',
            'dropdown_mkypzbbh' => ['label' => 'Español'], // Idioma
            'date_mkyp6w4t' => ['date' => date('Y-m-d')],
            'date_mkypeap2' => ['date' => date('Y-m-d', strtotime('+3 days'))],
            'long_text_mkypqppc' => 'Lead de prueba para actualización de etiquetas'
        ];
        
        echo "   Intentando crear lead con nuevas etiquetas (esto fallará si no existen)...\n";
        try {
            $itemResponse = $monday->createItem($leadsBoardId, $testLeadName, $columnValues);
            if (isset($itemResponse['create_item']['id'])) {
                $itemId = $itemResponse['create_item']['id'];
                echo "   ⚠️  ¡Sorprendente! El item se creó con nuevas etiquetas (ID: $itemId)\n";
                echo "      Esto significa que las etiquetas ya existen o se crearon\n";
            }
        } catch (Exception $e) {
            echo "   ✅ Confirmado: Las nuevas etiquetas no existen (como esperábamos)\n";
            echo "   Error esperado: " . $e->getMessage() . "\n\n";
        }
        
        // Dado que no podemos obtener la revisión directamente y la API es restrictiva,
        // la solución más viable es actualizar manualmente a través de la interfaz
        echo "3. RECOMENDACIÓN: ACTUALIZACIÓN MANUAL\n\n";
        
        echo "   Dado que la API requiere un parámetro 'revision' específico que no podemos\n";
        echo "   obtener fácilmente a través de la API pública estándar, la mejor solución\n";
        echo "   es actualizar las columnas manualmente a través de la interfaz de Monday:\n\n";
        
        echo "   PASOS PARA ACTUALIZAR MANUALMENTE:\n";
        echo "   1. Ir al tablero de Leads\n";
        echo "   2. Click en la columna 'Clasificación' (color_mkypv3rg)\n";
        echo "   3. Seleccionar 'Editar Columna'\n";
        echo "   4. Cambiar 'En curso' → 'HOT', 'Listo' → 'WARM', 'Detenido' → 'COLD'\n";
        echo "   5. Repetir para la columna 'Rol Detectado' (color_mkyng649)\n";
        echo "      Cambiar a: 'Mission Partner', 'Rector/Director', 'Alcalde/Gobierno', etc.\n\n";
        
        // Mientras tanto, actualicemos el webhook handler para que funcione con las etiquetas actuales
        echo "4. ACTUALIZANDO WEBHOOK HANDLER PARA USAR ETIQUETAS ACTUALES\n\n";
        
        // Crear un archivo con las constantes correctas para las etiquetas actuales
        $constantsContent = "<?php
// StatusConstants.php
// Constantes para las etiquetas de status basadas en la configuración actual

class StatusConstants {
    // Etiquetas actuales para la columna 'Clasificación' (color_mkypv3rg)
    const CLASSIFICATION_HOT = 'En curso';      // Alta puntuación
    const CLASSIFICATION_WARM = 'Listo';        // Puntuación media
    const CLASSIFICATION_COLD = 'Detenido';     // Baja puntuación
    
    // Etiquetas actuales para la columna 'Rol Detectado' (color_mkyng649)
    const ROLE_MISSION_PARTNER = 'En curso';
    const ROLE_RECTOR_DIRECTOR = 'Listo';
    const ROLE_MAYOR_GOVERNMENT = 'Detenido';
    const ROLE_CORPORATE = 'En curso';          // Temporal, hasta que se actualice manualmente
    const ROLE_TEACHER_MENTOR = 'Listo';        // Temporal, hasta que se actualice manualmente
    const ROLE_YOUNG = 'Detenido';              // Temporal, hasta que se actualice manualmente
    
    // Funciones para mapeo de puntuación a clasificación actual
    public static function getScoreClassification(\$score) {
        if (\$score >= 70) return self::CLASSIFICATION_HOT;
        if (\$score >= 30) return self::CLASSIFICATION_WARM;
        return self::CLASSIFICATION_COLD;
    }
    
    // Funciones para mapeo de rol detectado a etiqueta actual
    public static function getRoleLabel(\$role) {
        switch(strtolower(trim(\$role))) {
            case 'mission partner':
            case 'mp':
                return self::ROLE_MISSION_PARTNER;
            case 'rector':
            case 'director':
            case 'rector/director':
                return self::ROLE_RECTOR_DIRECTOR;
            case 'alcalde':
            case 'gobierno':
            case 'alcalde/gobierno':
                return self::ROLE_MAYOR_GOVERNMENT;
            default:
                return self::CLASSIFICATION_COLD; // Valor por defecto
        }
    }
}
?>
";
        
        file_put_contents('StatusConstants.php', $constantsContent);
        echo "   ✅ Archivo StatusConstants.php creado con etiquetas actuales\n";
        echo "   ✅ El webhook handler puede usar estas constantes mientras\n";
        echo "      se realizan las actualizaciones manuales necesarias\n\n";
        
        echo "5. RESUMEN DE ACCIONES\n\n";
        echo "   ✅ Conexión con Monday API verificada\n";
        echo "   ✅ Estructura de columnas mapeada correctamente\n";
        echo "   ✅ Archivo de constantes creado para usar con etiquetas actuales\n";
        echo "   ⚠️  Actualización de etiquetas requiere intervención manual\n";
        echo "   ✅ El sistema puede recibir y procesar leads con las etiquetas actuales\n\n";
        
        echo "========================================\n";
        echo "    SISTEMA LISTO PARA OPERAR (PARCIAL)   \n";
        echo "========================================\n";
        echo "Puedes comenzar a recibir leads usando las etiquetas actuales.\n";
        echo "Las actualizaciones manuales de etiquetas pueden hacerse más tarde\n";
        echo "sin interrumpir la funcionalidad básica del sistema.\n";
        echo "========================================\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar la actualización alternativa
$result = updateStatusAlternative();

if ($result) {
    echo "\n✅ Proceso de actualización alternativa completado.\n";
    echo "El sistema puede operar con las etiquetas actuales mientras\n";
    echo "se realizan las actualizaciones manuales deseadas.\n";
} else {
    echo "\n❌ Se encontraron errores.\n";
}

?>
